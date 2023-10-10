<?php
require_once 'defines.php';
require_once 'database.php';
?>


<!DOCTYPE html>
<html>

<head>
    <title>Facebook Login JavaScript Example</title>
    <meta charset="UTF-8">
    <style>
        #instaMedia label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        #instaMedia input[type="radio"] {
            display: none;
            /* Hide the default radio button */
        }

        #autoresize {
            display: block;
            overflow: :auto;
            resize: auto;
            background-color: #ffffff;
            border: 0px;
        }

        #instaMedia img {
            margin-right: 10px;
            /* Adjust spacing as needed */
            max-width: 100px;
            /* Adjust image size as needed */
            border: 2px solid #ccc;
            padding: 2px;
        }

        #instaMedia input[type="radio"]:checked+img {
            border-color: #007BFF;
            /* Highlight the selected image */
        }

        .media-span {
            font-size: 18px;
            font-weight: 600;
            font-family: monospace;
        }

        .caption {
            resize: none;
            height: 50px;
        }

        .textinput {
            height: 30px;
        }

        .content-center {
            display: flex;
            justify-content: center;
        }

        .btn {
            background-color: #007BFF;
            color: #ffffff;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease-in-out;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.6;
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
    </style>

</head>

<body>

    <div class="content-center" id="loginToFb">
        <fb:login-button id="loginBtn" scope="public_profile, email, pages_manage_ads, pages_manage_metadata, pages_read_engagement, pages_read_user_content, pages_messaging, ads_read, business_management, page_events, instagram_basic, instagram_manage_messages, instagram_manage_comments, instagram_manage_insights" onlogin="checkLoginState();">
        </fb:login-button>
    </div>
    <br>
    <div class="content-center">
        <div id="status">
        </div>
    </div>
    <br>
    <div class="content-center">
        <div id="">
            <select name="page_list" id="page_list" style="width: 200px;" required>
                <option value="">select a page</option>
            </select>
        </div>
    </div>
    <br>
    <div class="content-center"><button type="button" class="btn" onclick="getIg()">Continue</button></div>
    <br><br>
    <div class="content-center">
        <div id="insta">
            <div id="message"></div>
            <div>
                <img src="" alt="" id="image" style="border-radius: 50%; width: 200px; height: auto;">
            </div>
            <div id="username"></div>
            <div id="bio"></div>
        </div>
    </div>
    <br><br>
    <div class="content-center">
        <div id="instaMedia">
        </div>
    </div>

    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
    <script>
        function statusChangeCallback(response) {

            if (response.status === 'connected') {
                testAPI();
                var data = {
                    userId: response.authResponse.userID,
                    accessToken: response.authResponse.accessToken
                };
                makeAjaxCall(data)
            } else {
                document.getElementById('status').innerHTML = 'Please log ' +
                    'into this webpage.';
            }
        }


        function checkLoginState() {
            FB.getLoginStatus(function(response) {
                statusChangeCallback(response);
            });
        }


        window.fbAsyncInit = function() {
            FB.init({
                appId: '<?php echo FACEBOOK_APP_ID ?>',
                cookie: true,
                xfbml: true,
                version: '<?php echo FACEBOOK_APP_VERSION ?>'
            });


            FB.getLoginStatus(function(response) {
                // console.log(response); // user_id and access token
                statusChangeCallback(response); // Returns the login status.
            });
        };

        function testAPI() { // Testing Graph API after login.  See statusChangeCallback() for when this call is made.
            // console.log('Welcome!  Fetching your information.... ');
            FB.api('/me', function(response) {
                // console.log('Successful login for: ' + response.name);
                document.getElementById('status').innerHTML =
                    'Thanks for logging in, ' + response.name + '!';
            });
        }
    </script>
    <script>
        var globalResponse;

        function makeAjaxCall(data) {
            var xhr = new XMLHttpRequest();

            xhr.open("POST", "api.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            var jsonData = JSON.stringify(data);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // console.log(xhr.responseText);
                    document.getElementById("loginToFb").classList.toggle("disabled")
                    globalResponse = JSON.parse(xhr.responseText);
                    for (const key in globalResponse['data']) {
                        const newOption = document.createElement('option');

                        newOption.value = globalResponse['data'][key]['id'];
                        newOption.text = globalResponse['data'][key]['name'];

                        document.getElementById("page_list").appendChild(newOption);
                    }
                }
            };

            xhr.send(jsonData);
        }

        function getIg() {
            var pageId = document.getElementById("page_list").value;
            var select = document.getElementById("page_list");
            var pageName = select.options[select.selectedIndex].textContent;
            if (!pageId) {
                alert('Select A Page!');
                return;
            }

            for (const key in globalResponse['data']) {
                if (globalResponse['data'][key]['id'] == pageId) {
                    accessToken = globalResponse['data'][key]['access_token']
                    break;
                }
            }
            var xhr = new XMLHttpRequest();
            var data = {
                userId: globalResponse.userId,
                pageId: pageId,
                pageName: pageName,
                accessToken: accessToken
            };

            xhr.open("POST", "getInsta.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            var jsonData = JSON.stringify(data);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);

                    if (response.status == 1) {
                        document.getElementById("message").innerHTML = '';
                        document.getElementById("image").src = response['profile_picture_url'];
                        document.getElementById("username").innerHTML = 'username : ' + response.username
                        document.getElementById("bio").innerHTML = 'bio : ' + response.biography
                        getInstaPost(response.id);
                    } else {
                        document.getElementById("image").src = '';
                        document.getElementById("username").innerHTML = ''
                        document.getElementById("name").innerHTML = ''
                        document.getElementById("message").innerHTML = response.message
                    }

                }
            };

            xhr.send(jsonData);
        }

        function getInstaPost(instaId) {
            var xhr = new XMLHttpRequest();

            xhr.open("POST", "getInstaPost.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            var data = {
                instaId: instaId
            }

            var jsonData = JSON.stringify(data);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {

                    var response = JSON.parse(xhr.responseText);
                    document.getElementById("instaMedia").innerHTML = '';
                    var spanContainer = document.createElement("span");
                    spanContainer.innerHTML = 'Available Media';
                    spanContainer.className = 'media-span';
                    document.getElementById("instaMedia").appendChild(spanContainer);
                    for (const key in response['data']) {
                        var radioHTML = `
                                        <input type="radio" name="option" id="option${key}" value="${response['data'][key]['ig_id']}">
                                        <img src="${response['data'][key]['media_url']}" alt="${response['data'][key]['caption']}">
                                        <span style="padding-right: 5px;">Caption:</span>
                                        <textarea id="autoresize" cols="15" rows="1" TextMode="Multline" disabled>${response['data'][key]['caption']}</textarea>
                                        <span style="padding-left: 30px;padding-right: 5px;">Text or Link to send:</span>
                                        <input type="text" class="textinput" id="input_option${key}" value="">
                                        <div class="saveBtn" style="padding-left: 10px;padding-right: 5px;" >
                                            <button type="button" id="saveBtn" onclick="savePostData('option${key}','${response['data'][key]['ig_id']}','${response['pageId']}')">Save</button>
                                        </div>
                                    `;

                        var parentElement = document.getElementById("instaMedia");
                        var container = document.createElement("label");
                        container.innerHTML = radioHTML;
                        parentElement.appendChild(container);
                        var data = {
                            mediaId: response['data'][key]['ig_id'],
                            pageId: response['pageId']
                        }
                        checkForTextToSend(data, key);
                    }
                }
            };

            xhr.send(jsonData);
        }

        function savePostData(elementId, mediaId, pageId) {
            var text = document.getElementById("input_" + elementId).value;

            var data = {
                textToSend: text,
                mediaId: mediaId,
                pageId: pageId
            }

            var xhr = new XMLHttpRequest();

            xhr.open("POST", "savePostData.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            var jsonData = JSON.stringify(data);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status) {
                        alert('Saved Successfully!');
                    } else {
                        alert('Something Went Wrong!');
                    }
                }
            };

            xhr.send(jsonData);

        }

        function checkForTextToSend(data, key) {
            var xhr = new XMLHttpRequest();

            xhr.open("POST", "getTextToSend.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            var jsonData = JSON.stringify(data);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status) {
                        document.getElementById("input_option" + key).value = response.message;
                    } else {
                        alert('Something Went Wrong!');
                    }
                }
            };

            xhr.send(jsonData);
        }
    </script>
</body>

</html>