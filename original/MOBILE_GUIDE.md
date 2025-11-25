# DICOM Viewer Pro - Mobile Guide

## Mobile Optimizations

The DICOM viewer has been fully optimized for mobile devices with touch-friendly controls and responsive layout.

## What's New

### Mobile-First Design
- Fully responsive layout that adapts to any screen size
- Touch-optimized controls for easy interaction
- Simplified interface on mobile devices
- Fast and smooth performance

### Mobile-Specific Features

#### 1. **Bottom Toolbar** (Mobile Only)
Essential tools accessible at the bottom of the screen:

- **Pan** - Move the image around
- **Zoom** - Zoom in/out on the image
- **W/L** - Adjust Window/Level (brightness/contrast)
- **Images** - View all images as thumbnails
- **Full** - Toggle fullscreen mode

**Usage:** Tap any tool to activate it, then use touch gestures on the image.

#### 2. **Image Thumbnails**
- Tap the "Images" button to show all images
- Horizontal scrollable thumbnail strip
- Tap any thumbnail to view that image
- Current image is highlighted in blue
- Numbers show image sequence

#### 3. **Fullscreen Mode**
- Tap "Full" button to enter fullscreen
- Hides all UI elements for distraction-free viewing
- Shows only the DICOM image
- Tap "Exit" to return to normal view
- Works on all mobile browsers

#### 4. **Touch Gestures**
- **Single finger drag** - Use active tool (pan/zoom/W-L)
- **Pinch-to-zoom** - Quick zoom with two fingers
- **Swipe** - Navigate between images (when implemented)
- **Double tap** - Reset view (when implemented)

### Desktop Features (Hidden on Mobile)
The following are hidden on mobile for cleaner interface:
- Upload controls
- Export controls
- Settings button
- Right sidebar (tools panel)
- MPR controls
- Advanced layout options

## Screen Size Breakpoints

### Mobile (< 768px)
- Single column layout
- Bottom toolbar
- Collapsible series list
- Full-width viewport
- Essential tools only

### Tablet (768px - 1199px)
- Two-column layout
- Left sidebar visible
- Desktop toolbar
- All tools available

### Desktop (>= 1200px)
- Three-column layout
- Both sidebars visible
- Full feature set
- MPR capabilities

## Mobile Usage Instructions

### Viewing a DICOM Study

1. **Load Study**
   - Study loads automatically from URL parameters
   - Or use desktop mode to upload files

2. **Navigate Images**
   - Tap "Images" button to see all images
   - Tap thumbnail to jump to specific image
   - Use prev/next buttons in series list

3. **Adjust View**
   - **Brightness/Contrast**: Tap "W/L" then drag on image
     - Drag left/right: Change window width (contrast)
     - Drag up/down: Change window level (brightness)

   - **Zoom**: Tap "Zoom" then drag on image
     - Drag up: Zoom in
     - Drag down: Zoom out
     - Or use pinch gesture

   - **Pan**: Tap "Pan" then drag to move image

4. **Fullscreen**
   - Tap "Full" button for distraction-free viewing
   - Rotate device for best viewing angle
   - All gestures work in fullscreen
   - Tap "Exit" or press device back button

### Tips for Best Experience

**Portrait Mode:**
- Best for viewing single images
- Easier to access bottom toolbar
- Good for scrolling through thumbnails

**Landscape Mode:**
- Best for detailed image viewing
- Wider viewport for anatomical structures
- Better for fullscreen mode

**Performance Tips:**
- Close other browser tabs
- Use Wi-Fi for large studies
- Clear browser cache if slow
- Restart browser if issues occur

## Keyboard Shortcuts (External Keyboard)

If you have a keyboard connected:
- `Arrow Keys` - Navigate images
- `F` - Toggle fullscreen
- `R` - Reset view
- `Esc` - Exit fullscreen
- `Space` - Play/pause cine
- `1` - Pan tool
- `2` - Zoom tool
- `3` - Window/Level tool

## Browser Compatibility

### Tested Browsers
- ✅ Chrome (Android/iOS) - Recommended
- ✅ Safari (iOS) - Recommended
- ✅ Firefox (Android/iOS)
- ✅ Edge (Android)
- ✅ Samsung Internet

### Requirements
- Modern browser (updated in last 2 years)
- JavaScript enabled
- Minimum screen size: 320px
- Touch screen or mouse

## Troubleshooting

### Images Not Loading
1. Check internet connection
2. Refresh the page
3. Clear browser cache
4. Try different browser

### Fullscreen Not Working
1. Some browsers require user interaction first
2. Try tapping screen before entering fullscreen
3. Check browser settings allow fullscreen
4. Some iOS versions have limitations

### Touch Gestures Not Responding
1. Ensure correct tool is selected (check blue highlight)
2. Touch directly on the image viewport
3. Avoid touching UI elements
4. Try refreshing the page

### Thumbnails Not Showing
1. Images must be fully loaded first
2. Tap "Images" button again
3. Check if multiple images in series
4. Refresh if needed

### Performance Issues
1. **Slow Loading:**
   - Switch to Wi-Fi
   - Close other apps
   - Clear browser cache

2. **Laggy Gestures:**
   - Reduce quality in settings (desktop mode)
   - Close other browser tabs
   - Restart browser

3. **Battery Drain:**
   - Exit fullscreen when not viewing
   - Close app when finished
   - Reduce screen brightness

## Feature Comparison

| Feature | Mobile | Desktop |
|---------|--------|---------|
| View DICOM images | ✅ | ✅ |
| Pan/Zoom/Window-Level | ✅ | ✅ |
| Fullscreen | ✅ | ✅ |
| Image thumbnails | ✅ | ⚠️ Desktop has series list |
| Touch gestures | ✅ | ⚠️ Mouse only |
| Upload files | ❌ | ✅ |
| MPR views | ❌ | ✅ |
| Measurements | ❌ | ✅ |
| Export images | ❌ | ✅ |
| Reports | ❌ | ✅ |
| Advanced tools | ❌ | ✅ |

## URLs for Mobile

**Access from mobile device:**
```
http://localhost/papa/dicom_again/index.php?studyUID=XXX&orthancId=XXX
```

**Or from production:**
```
https://e-connect.in/dicom/index.php?studyUID=XXX&orthancId=XXX
```

**From patient list:**
1. Go to patients.html on mobile
2. Tap patient name
3. Tap study
4. Tap "Open" button
5. Viewer opens automatically

## Technical Details

### Mobile CSS Classes
- `.mobile-tools-bar` - Bottom toolbar
- `.image-thumbnails` - Thumbnail selector
- `.fullscreen-mode` - Fullscreen state
- `.thumbnail-item` - Individual thumbnail
- `.sidebar.collapsed` - Collapsed sidebar

### JavaScript API
```javascript
// Activate tool
window.MobileControls.setActiveTool(button, 'Pan');

// Toggle fullscreen
window.MobileControls.toggleFullscreen();

// Show thumbnails
window.MobileControls.toggleImageThumbnails();

// Select image
window.MobileControls.selectImage(index);
```

### Touch Events
- `touchstart` - Detect touch begin
- `touchmove` - Track finger movement
- `touchend` - Detect touch release
- Pinch gesture using distance calculation

## Future Enhancements

Planned features for mobile:
- [ ] Swipe to change images
- [ ] Double-tap to reset view
- [ ] Cine mode playback
- [ ] Basic measurements
- [ ] Annotation tools
- [ ] Voice control
- [ ] Offline mode
- [ ] Share functionality

## Developer Notes

### Responsive Breakpoints
```css
@media (max-width: 767px)  /* Mobile */
@media (min-width: 768px)  /* Tablet */
@media (min-width: 1200px) /* Desktop */
```

### Key Files
- `index.php` - Main viewer with mobile CSS
- `js/components/mobile-controls.js` - Mobile functionality
- `css/styles.css` - Base responsive styles

### Testing
Test on:
- Real devices (iPhone, Android)
- Chrome DevTools device mode
- Different screen sizes
- Portrait and landscape
- Touch and mouse

## Support

For issues or questions:
1. Check console for errors (desktop browser)
2. Test on different browser
3. Clear cache and retry
4. Check network connection
5. Report to support team

---

**Note:** This viewer is optimized for viewing existing studies. For uploading new DICOM files, please use the desktop version at a computer.
