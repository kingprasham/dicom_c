# Default User Credentials

## Hospital DICOM Viewer Pro v2.0

### Default Users

After running the database schema, the following default users will be created:

**⚠️ LOGIN WITH EMAIL, NOT USERNAME!**

| Email (LOGIN)             | Password    | Username    | Role         |
|-------------------------- |------------ |------------ |------------- |
| admin@hospital.com        | Admin@123   | admin       | admin        |
| radiologist@hospital.com  | Radio@123   | radiologist | radiologist  |
| technician@hospital.com   | Tech@123    | technician  | technician   |

### User Roles & Permissions

#### Admin
- Full system access
- User management
- Sync configuration
- Backup management
- System settings
- All radiologist and technician permissions

#### Radiologist
- View studies
- Create/edit medical reports
- Add measurements and annotations
- Clinical notes
- Export/print reports
- Prescriptions

#### Technician
- View studies
- Upload DICOM files
- Basic measurements
- Clinical notes
- Limited report access (view only)

#### Viewer
- View studies only
- Basic image manipulation
- No edit permissions

### Security Notes

**IMPORTANT:** Change all default passwords immediately after first login!

### Password Requirements

- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character (@, #, $, %, etc.)

### First-Time Setup

1. Run the database schema: `mysql -u root -p < setup/schema_v2_production.sql`
2. Login as admin user
3. Change default passwords
4. Create additional users as needed
5. Configure system settings
6. Configure sync and backup systems

