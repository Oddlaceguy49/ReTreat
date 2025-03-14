<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mydatabase");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReTreat - Home</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            background-color: #f4f4f4; /* Light mode default background */
            color: #2e2e2e; /* Default text color */
            transition: background-color 0.3s, color 0.3s; /* Added transition */
        }

        .container {
			width: 80vw;
            height: 80vh;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            min-width: 400px;
            background-color: #f4f4f4; /* Light mode default container background */
            transition: background-color 0.3s; /* Added transition */
        }
		.post-box {
			text-align: right;
			border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
		}	

        /* Light mode styles */
        body:not(.dark-mode) .container {
            background-color: #f4f4f4;
        }

        body:not(.dark-mode) .post-box,
        body:not(.dark-mode) .posts-container,
        body:not(.dark-mode) textarea {
            background-color: #f4f4f4;
            color: black;
            border-color: #ccc;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s; /* Added transition */
        }

		.textarea {
			margin-top: 4px;
			margin-left: 4px;
			margin-right: 4px;
			text-align: center;
			resize: none;
		}
		
		textarea {
			width: 96.6%;
            height: 10vh;
            padding: 1vw;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
        }

        button {
            margin-right: 4px;
			margin-bottom: 4px;
            padding: 1vw 2vw;
            border: none;
            background-color: #007bff;
            color: #f4f4f4;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s; /* Added transition */
        }

        .posts-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 2vh 2vw;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 2vh;
            transition: background-color 0.3s; /* Added transition */
        }

        .posts-container::-webkit-scrollbar {
            display: none;
        }

        .posts-container {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .post {
            padding: 2vh;
            border-bottom: 1px solid #ddd;
        }

        .post:last-child {
            border-bottom: none;
        }

        #loading {
            display: none;
            font-size: 1.4vh;
            color: gray;
        }

        #message {
            color: green;
            margin-top: 2vh;
            font-size: 1.4vh;
            font-weight: bold;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #181818;
            color: #f4f4f4;
            transition: background-color 0.3s, color 0.3s; /* Added transition */
        }

        body.dark-mode .container {
            background-color: #181818;
            transition: background-color 0.3s; /* Added transition */
        }

        body.dark-mode .post-box,
        body.dark-mode .posts-container,
        body.dark-mode textarea {
            background-color: #181818;
            color: #f4f4f4;
            border-color: #555;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s; /* Added transition */
        }

        body.dark-mode button {
            background-color: #333;
            color: #f4f4f4;
            border: 1px solid #555;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s; /* Added transition */
        }

        body.dark-mode h1 {
            color: #f4f4f4;
            transition: color 0.3s; /* Added transition */
        }

        /* Mode toggle button */
        #mode-toggle {
            position: absolute;
            top: 10px;
            left: 10px;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            z-index: 1000;
            transition: background-color 0.3s, color 0.3s; /* Added transition */
        }
		
		#logout {
			position: absolute;
			bottom: 10px;
			left: 10px;
		}

        /* Default h1 style for light mode */
        h1 {
            color: #2e2e2e;
            transition: color 0.3s; /* Added transition */
        }
    </style>
</head>

<body>

<button id="logout" onclick="logout()">Logout</div>
<button id="mode-toggle" onclick="toggleMode()"><img src="http://localhost/retreat/assets/moon.png" width="40" height="40"></button>

<div class="container">
    <div class="post-box">
        <div class="textarea">
			<textarea id="postContent" placeholder="What's on your mind?"></textarea><br>
		</div>
        <button onclick="submitPost()">Post</button>
    </div>

    <div id="message"></div>

    <h1>Posts</h1>
    <div class="posts-container" id="posts"></div>
    <p id="loading">Loading more posts...</p>
</div>

<script>
let offset = 0;
const limit = 10;
let loading = false;
let allPostsLoaded = false;

function logout() {
	window.location.href = 'logout.php';
}

function loadPosts() {
    if (loading || allPostsLoaded) return;
    loading = true;
    $("#loading").show();

    $.get("load_posts.php", { offset: offset, limit: limit }, function(response) {
        let data = JSON.parse(response);

        if (data.length === 0) {
            allPostsLoaded = true;
            $("#loading").text("No more posts.");
            return;
        }

        data.forEach(post => {
            // Replace newline characters with <br> tags for proper line breaks in HTML
            let formattedContent = post.content.replace(/\n/g, "<br>");
            let newPost = `<div class='post'><p><strong>${post.username}:</strong></p><p>${formattedContent}</p></div>`;
            $("#posts").append(newPost);
        });

        offset += limit;
    }).fail(function() {
        $("#loading").text("Error loading posts.");
    }).always(function() {
        loading = false;
        $("#loading").hide();
    });
}


// Load initial posts
$(document).ready(function() {
    loadPosts();

    $(".posts-container").scroll(function() {
        let container = $(".posts-container")[0];
        if (container.scrollTop + container.clientHeight >= container.scrollHeight - 10) {
            loadPosts();
        }
    });
});

function formatDate(date) {
    let d = new Date(date);
    let year = d.getFullYear();
    let month = ("0" + (d.getMonth() + 1)).slice(-2); // Add leading zero if necessary
    let day = ("0" + d.getDate()).slice(-2); // Add leading zero if necessary
    let hours = ("0" + d.getHours()).slice(-2); // Add leading zero if necessary
    let minutes = ("0" + d.getMinutes()).slice(-2); // Add leading zero if necessary
    let seconds = ("0" + d.getSeconds()).slice(-2); // Add leading zero if necessary
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function submitPost() {
    let content = $("#postContent").val().trim();
    if (content === "") {
        $("#message").text("Post cannot be empty!").css("color", "red");
        return;
    }

    $.post("save_message.php", { content: content }, function(response) {
        // Check if the response is already a parsed object
        console.log(response);  // Log the raw response to ensure it's in the expected format

        // If response is already a parsed object, no need to use JSON.parse
        // Just directly use the response object
        if (response.error) {
            $("#message").text(response.error).css("color", "red");
        } else {
            $("#message").text(response.success).css("color", "green");

            // Replace newline characters with <br> tags
            let formattedContent = response.content.replace(/\n/g, '<br>');

            // Convert the GMT time to the user's local time
            let postTime = new Date(response.time + " GMT"); // Add " GMT" to ensure it's treated as GMT
            let localTime = formatDate(postTime); // Format the date and time as YYYY-MM-DD HH:mm:ss

            // Create a new post with formatted content and time
            let newPost = `
                <div class='post'>
                    <p><strong>${response.user}</strong> <strong>(${localTime}):</strong></p>
                    <p>${formattedContent}</p>
                </div>
            `;
            $("#posts").prepend(newPost);

            // Only load new posts AFTER successful submission
            loadPosts();
        }
    }).fail(function() {
        $("#message").text("Failed to post message.").css("color", "red");
    });

    // Clear the content input after submission
    $("#postContent").val("");
}

function toggleMode() {
    document.body.classList.toggle("dark-mode");
    const modeButton = document.getElementById("mode-toggle");
    if (document.body.classList.contains("dark-mode")) {
        modeButton.innerHTML = '<img src="http://localhost/retreat/assets/sun.png" width="40" height="40"></img>'; // Switch to sun icon for light mode
    } else {
        modeButton.innerHTML = '<img src="http://localhost/retreat/assets/moon.png" width="40" height="40"></img>'; // Switch to moon icon for dark mode
    }
}
</script>

</body>
</html>
