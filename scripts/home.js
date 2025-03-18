let offset = 0;
const limit = 10;
let loading = false;
let allPostsLoaded = false;
const baseDomain = window.location.hostname.includes("canary-ec4") 
  ? "canary" 
  : "";

// Function to get the cookie value by name
function getCookie(name) {
    let cookieArr = document.cookie.split("; ");
    for (let i = 0; i < cookieArr.length; i++) {
        let cookiePair = cookieArr[i].split("=");
        if (name === cookiePair[0]) {
            return decodeURIComponent(cookiePair[1]);
        }
    }
    return null;
}

// Retrieve accent color from cookie
const accentColor = getCookie("accentcolour") ? `#${getCookie("accentcolour")}` : "#007bff"; // Default color

// Do when content loads
document.addEventListener("DOMContentLoaded", function () {
    // Set accent color for buttons
    document.querySelectorAll("button").forEach(button => {
        button.style.backgroundColor = accentColor;
    });

    // Grab Dark Mode setting from storage
    if (localStorage.getItem("dark-mode") === "true") {
        document.body.classList.add("dark-mode");
        document.getElementById("mode-toggle").innerHTML = '<img src="/assets/sun.png" width="40" height="40">';
    } else {
        document.getElementById("mode-toggle").innerHTML = '<img src="/assets/moon.png" width="40" height="40">';
    }
    
    loadPosts(); // Load posts when the page loads

    document.getElementById("posts").addEventListener("scrollend", (event) => {
        loadPosts();
    });
});

function settings() {
    window.location.href = 'settings.html';
}

function logout() {
    window.location.href = 'logout.php';
}

// Function to load posts from the Worker API
async function loadPosts() {
    if (loading || allPostsLoaded) return;
    loading = true;
    document.getElementById("loading").style.display = "block";

    try {
        const response = await fetch(`https://${baseDomain}getposts.retreat.workers.dev/?offset=${offset}&limit=${limit}`);
        const data = await response.json();

        if (response.ok && data.success) {
            if (data.posts.length === 0) {
                allPostsLoaded = true;
                document.getElementById("loading").innerText = "No more posts.";
                return;
            }

            data.posts.forEach(post => {
                let formattedContent = post.content.replace(/\n/g, "<br>");
                let newPost = `<div class='post'><p><strong>${post.username}:</strong></p><p>${formattedContent}</p></div>`;
                document.getElementById("posts").innerHTML += newPost;
            });

            offset += limit;
        } else {
            throw new Error("Failed to fetch posts.");
        }
    } catch (error) {
        console.error("Error:", error);
        document.getElementById("loading").innerText = "Failed to load posts.";
    } finally {
        loading = false;
        document.getElementById("loading").style.display = "none";
    }
}

// Function to submit a new post to the Worker API
function submitPost() {
    let content = document.getElementById("postContent").value.trim();
    let username = getCookie("username"); // Retrieve username from cookie

    if (!username) {
        document.getElementById("message").innerText = "You must be logged in to post!";
        document.getElementById("message").style.color = "red";
        return;
    }

    if (content === "") {
        document.getElementById("message").innerText = "Post cannot be empty!";
        document.getElementById("message").style.color = "red";
        return;
    }

    fetch(`https://${baseDomain}createpost.retreat.workers.dev`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, content }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById("message").innerText = "Post successful!";
            document.getElementById("message").style.color = "green";

            let newPost = `<div class='post'><p><strong>${username}:</strong></p><p>${content}</p></div>`;
            document.getElementById("posts").insertAdjacentHTML('afterbegin', newPost);
        } else {
            document.getElementById("message").innerText = "Error: " + data.error;
            document.getElementById("message").style.color = "red";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("message").innerText = "Server error.";
        document.getElementById("message").style.color = "red";
    });

    document.getElementById("postContent").value = "";
}

function toggleMode() {
    document.body.classList.toggle("dark-mode");
    const modeButton = document.getElementById("mode-toggle");

    if (document.body.classList.contains("dark-mode")) {
        modeButton.innerHTML = '<img src="/assets/sun.png" width="40" height="40">';
        localStorage.setItem("dark-mode", "true");
    } else {
        modeButton.innerHTML = '<img src="/assets/moon.png" width="40" height="40">';
        localStorage.setItem("dark-mode", "false");
    }
}
