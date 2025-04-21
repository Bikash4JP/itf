const videoElement = document.getElementById('service-video');
    
    // Array of possible thumbnails
    const thumbnails = [
        'images/thumbnail1.png', // Replace with actual thumbnail file paths
        'images/thumbnail2.png',
        'images/thumbnail3.png'
    ];

    // Set random thumbnail
    videoElement.poster = thumbnails[Math.floor(Math.random() * thumbnails.length)];

    // Play full video with controls when clicked
    videoElement.addEventListener('click', () => {
        videoElement.controls = true; // Enable controls on click
        videoElement.style.width = '100%'; // Ensure it expands fully
        videoElement.style.height = 'auto'; // Adjust height
        videoElement.play(); // Play video
    });

    // Autoplay and mute when hovered
    videoElement.addEventListener('mouseenter', () => {
        videoElement.play(); // Ensure autoplay when hover
        videoElement.muted = true; // Mute on hover
    });

    // Unmute when mouse leaves
    videoElement.addEventListener('mouseleave', () => {
        videoElement.muted = false; // Unmute when hover ends
    });