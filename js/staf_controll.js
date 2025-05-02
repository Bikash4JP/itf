function fetchPosts() {
    fetch('php/fetch_staff_posts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const postsList = document.getElementById('postsList');
                postsList.innerHTML = data.posts.map(post => `
                    <div class="post">
                        <h3>${post.title}</h3>
                        <button onclick="editPost(${post.id})">Edit</button>
                    </div>
                `).join('');
            }
        })
        .catch(error => console.error('Error:', error));
}

function editPost(postId) {
    window.location.href = `edit_post.php?id=${postId}`;
}

// Call fetchPosts on page load
document.addEventListener('DOMContentLoaded', fetchPosts);