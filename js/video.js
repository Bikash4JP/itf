const videoElement = document.getElementById('service-video');

// Array of possible thumbnails
const thumbnails = [
    'images/thumbnail1.png', // Replace with actual thumbnail file paths
    'images/thumbnail2.png',
    'images/thumbnail3.png'
];

// Set random thumbnail as the default poster
videoElement.poster = thumbnails[Math.floor(Math.random() * thumbnails.length)];

// Ensure video is paused and muted by default
videoElement.pause();
videoElement.muted = true;
videoElement.controls = false; // Hide controls initially

// On hover, play video in muted mode and show controls
videoElement.addEventListener('mouseenter', () => {
    videoElement.muted = true; // Ensure muted
    videoElement.controls = true; // Show controls on hover
    videoElement.play(); // Play video on hover
});

// On mouse leave, pause the video, hide controls, and reset to thumbnail
videoElement.addEventListener('mouseleave', () => {
    videoElement.pause(); // Pause video
    videoElement.controls = false; // Hide controls
    videoElement.currentTime = 0; // Reset video to start
    videoElement.muted = true; // Ensure muted
});

// When the user clicks the video or the play button in controls
videoElement.addEventListener('click', (e) => {
    // Check if the click is on the video itself or the play button in controls
    if (!videoElement.paused) {
        videoElement.muted = false; // Unmute only if the video is already playing (i.e., user clicked play)
        videoElement.controls = true; // Ensure controls remain visible
        videoElement.style.width = '100%'; // Expand to full width
        videoElement.style.height = 'auto'; // Adjust height
    }
});

// When the video ends, reset to thumbnail
videoElement.addEventListener('ended', () => {
    videoElement.pause();
    videoElement.controls = false; // Hide controls
    videoElement.currentTime = 0; // Reset to start
    videoElement.muted = true; // Mute again
});