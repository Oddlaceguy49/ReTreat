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
    <title>Retreat - Home</title>
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
			margin-top: ;
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
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            z-index: 1000;
            transition: background-color 0.3s, color 0.3s; /* Added transition */
        }

        /* Default h1 style for light mode */
        h1 {
            color: #2e2e2e;
            transition: color 0.3s; /* Added transition */
        }
    </style>
</head>

<body>

<button id="mode-toggle" onclick="toggleMode()">🌙</button>

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

function submitPost() {
    let content = $("#postContent").val().trim();
    if (content === "") {
        $("#message").text("Post cannot be empty!").css("color", "red");
        return;
    }

    $.post("save_message.php", { content: content }, function(response) {
        let data = JSON.parse(response);
        if (data.error) {
            $("#message").text(data.error).css("color", "red");
        } else {
            $("#message").text(data.success).css("color", "green");

            // Replace newline characters with <br> tags
            let formattedContent = data.content.replace(/\n/g, '<br>');

            // Convert the GMT time to the user's local time
            let postTime = new Date(data.time + " GMT"); // Add " GMT" to ensure it's treated as GMT
            let localTime = postTime.toLocaleString(); // Convert to local time

            // Create a new post with formatted content and time
            let newPost = `
                <div class='post'>
                    <p><strong>${data.user}:</strong></p>
                    <p>${formattedContent}</p>
                    <p><small>Posted at ${localTime}</small></p>
                </div>
            `;
            $("#posts").prepend(newPost);
        }
    }).fail(function() {
        $("#message").text("Failed to post message.").css("color", "red");
    });

    $("#postContent").val("");
}

function toggleMode() {
    document.body.classList.toggle("dark-mode");
    const modeButton = document.getElementById("mode-toggle");
    if (document.body.classList.contains("dark-mode")) {
        modeButton.innerHTML = "🌞"; // Switch to sun icon for light mode
    } else {
        modeButton.innerHTML = "🌙"; // Switch to moon icon for dark mode
    }
}
</script>

</body>
</html>
