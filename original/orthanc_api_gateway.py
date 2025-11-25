"""
DICOM Orthanc API Gateway
Secure proxy between remote web app and local Orthanc server
"""

from flask import Flask, request, Response, jsonify
import requests
from requests.auth import HTTPBasicAuth
import logging
import os
from datetime import datetime

# Configuration
ORTHANC_URL = 'http://localhost:8042'
ORTHANC_USER = 'orthanc'
ORTHANC_PASS = 'orthanc'
API_KEY = 'Hospital2025_DicomSecureKey_XyZ789ABC'
CACHE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'dicom_cache')

# Setup Flask app
app = Flask(__name__)

# Setup logging with absolute paths
LOG_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'logs')
if not os.path.exists(LOG_DIR):
    os.makedirs(LOG_DIR)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(os.path.join(LOG_DIR, 'dicom_api_gateway.log')),
        logging.StreamHandler()
    ]
)

# Create cache directory
if not os.path.exists(CACHE_DIR):
    os.makedirs(CACHE_DIR)
    logging.info(f"Created cache directory: {CACHE_DIR}")

def verify_api_key():
    """Verify the API key from request headers"""
    api_key = request.headers.get('X-API-Key')
    if api_key != API_KEY:
        logging.warning(f"Unauthorized access from {request.remote_addr}")
        return False
    return True

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint - no auth required"""
    try:
        # Check if Orthanc is accessible
        response = requests.get(
            f"{ORTHANC_URL}/system",
            auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
            timeout=5
        )
        orthanc_status = "healthy" if response.status_code == 200 else "unhealthy"
    except Exception as e:
        orthanc_status = f"error: {str(e)}"

    return jsonify({
        'status': 'running',
        'timestamp': datetime.now().isoformat(),
        'orthanc': orthanc_status,
        'cache_dir': CACHE_DIR
    })

@app.route('/gateway/studies/<string:study_id>/instances', methods=['GET'])
def get_study_instances(study_id):
    """Get all instances for a study with full metadata"""
    logging.info(f"[CUSTOM ROUTE] Getting instances for study: {study_id}")
    if not verify_api_key():
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401

    try:
        # Get study metadata from Orthanc
        response = requests.get(
            f"{ORTHANC_URL}/studies/{study_id}",
            auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
            timeout=30
        )

        if response.status_code != 200:
            return jsonify({'success': False, 'error': 'Study not found'}), 404

        study_data = response.json()

        # Collect all instances with metadata
        instances = []
        for series_id in study_data.get('Series', []):
            series_response = requests.get(
                f"{ORTHANC_URL}/series/{series_id}",
                auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
                timeout=30
            )

            if series_response.status_code == 200:
                series_data = series_response.json()
                series_main_tags = series_data.get('MainDicomTags', {})
                series_uid = series_main_tags.get('SeriesInstanceUID', series_id)
                series_desc = series_main_tags.get('SeriesDescription', 'Series')
                series_num = series_main_tags.get('SeriesNumber', '0')

                # Get each instance metadata
                for instance_id in series_data.get('Instances', []):
                    instance_response = requests.get(
                        f"{ORTHANC_URL}/instances/{instance_id}",
                        auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
                        timeout=10
                    )

                    if instance_response.status_code == 200:
                        instance_data = instance_response.json()
                        instance_tags = instance_data.get('MainDicomTags', {})

                        instances.append({
                            'instanceId': instance_id,
                            'seriesInstanceUID': series_uid,
                            'sopInstanceUID': instance_tags.get('SOPInstanceUID', instance_id),
                            'instanceNumber': int(instance_tags.get('InstanceNumber', 0)),
                            'seriesDescription': series_desc,
                            'seriesNumber': int(series_num)
                        })

        logging.info(f"Fetched {len(instances)} instances for study {study_id}")
        return jsonify({'success': True, 'instances': instances})

    except Exception as e:
        logging.error(f"Error fetching study instances: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/instances/<instance_id>/file', methods=['GET', 'HEAD'])
def get_dicom_file(instance_id):
    """Get DICOM file with caching"""
    if not verify_api_key():
        return jsonify({'error': 'Unauthorized'}), 401

    try:
        # Check cache first
        subdir = instance_id[:2]
        cache_path = os.path.join(CACHE_DIR, subdir, f"{instance_id}.dcm")

        if os.path.exists(cache_path):
            logging.info(f"Serving {instance_id} from cache")

            if request.method == 'HEAD':
                return Response(
                    status=200,
                    headers={
                        'Content-Type': 'application/dicom',
                        'Content-Length': str(os.path.getsize(cache_path))
                    }
                )

            with open(cache_path, 'rb') as f:
                file_data = f.read()

            return Response(
                file_data,
                mimetype='application/dicom',
                headers={
                    'Content-Disposition': f'inline; filename="{instance_id}.dcm"',
                    'Content-Length': str(len(file_data))
                }
            )

        # Fetch from Orthanc
        response = requests.get(
            f"{ORTHANC_URL}/instances/{instance_id}/file",
            auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
            timeout=60
        )

        if response.status_code != 200:
            return jsonify({'error': 'Instance not found'}), 404

        file_data = response.content

        # Cache the file
        cache_subdir = os.path.join(CACHE_DIR, subdir)
        if not os.path.exists(cache_subdir):
            os.makedirs(cache_subdir)

        with open(cache_path, 'wb') as f:
            f.write(file_data)

        logging.info(f"Cached {instance_id} ({len(file_data)} bytes)")
        logging.info(f"Served {instance_id} from Orthanc")

        if request.method == 'HEAD':
            return Response(
                status=200,
                headers={
                    'Content-Type': 'application/dicom',
                    'Content-Length': str(len(file_data))
                }
            )

        return Response(
            file_data,
            mimetype='application/dicom',
            headers={
                'Content-Disposition': f'inline; filename="{instance_id}.dcm"',
                'Content-Length': str(len(file_data))
            }
        )

    except Exception as e:
        logging.error(f"Error serving DICOM file: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/orthanc/<path:endpoint>', methods=['GET', 'POST', 'PUT', 'DELETE'])
def orthanc_proxy(endpoint):
    """General proxy for Orthanc API"""
    if not verify_api_key():
        return jsonify({'error': 'Unauthorized'}), 401

    try:
        url = f"{ORTHANC_URL}/{endpoint}"

        # Forward the request to Orthanc
        response = requests.request(
            method=request.method,
            url=url,
            auth=HTTPBasicAuth(ORTHANC_USER, ORTHANC_PASS),
            params=request.args,
            data=request.get_data(),
            headers={'Content-Type': request.headers.get('Content-Type', 'application/json')},
            timeout=60
        )

        # Filter out hop-by-hop headers that WSGI doesn't allow
        hop_by_hop = {
            'connection', 'keep-alive', 'proxy-authenticate',
            'proxy-authorization', 'te', 'trailers', 'transfer-encoding', 'upgrade'
        }
        filtered_headers = {
            key: value for key, value in response.headers.items()
            if key.lower() not in hop_by_hop
        }

        # Return the response
        return Response(
            response.content,
            status=response.status_code,
            headers=filtered_headers
        )

    except Exception as e:
        logging.error(f"Error proxying to Orthanc: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    logging.info("="*60)
    logging.info("DICOM Orthanc API Gateway Starting")
    logging.info(f"Orthanc URL: {ORTHANC_URL}")
    logging.info(f"Cache Directory: {CACHE_DIR}")
    logging.info(f"API Key: {API_KEY[:20]}...")
    logging.info("="*60)

    # Use waitress for production deployment (works as Windows service)
    try:
        from waitress import serve
        logging.info("Starting with Waitress production server...")
        serve(app, host='0.0.0.0', port=5000, threads=6)
    except ImportError:
        logging.warning("Waitress not found, using Flask development server...")
        # Fallback to Flask dev server (not recommended for production)
        app.run(host='0.0.0.0', port=5000, debug=False)
