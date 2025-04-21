<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Chat Widget Clone</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom CSS with animations */
        :root {
            --fb-blue: #1877f2;
            --fb-light-blue: #e7f3ff;
            --fb-dark-blue: #0d5bd1;
            --fb-gray: #f0f2f5;
            --fb-dark-gray: #65676b;
            --fb-green: #42b72a;
        }
        
        /* Widget container */
        .fb-chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            z-index: 1000;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            animation: fadeInUp 0.3s ease-out forwards;
        }
        
        /* Header */
        .fb-chat-header {
            background-color: var(--fb-blue);
            color: white;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .fb-chat-header:hover {
            background-color: var(--fb-dark-blue);
        }
        
        .fb-chat-header .title {
            font-weight: 600;
            font-size: 16px;
        }
        
        .fb-chat-header .controls {
            display: flex;
            gap: 15px;
        }
        
        .fb-chat-header .controls i {
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .fb-chat-header .controls i:hover {
            transform: scale(1.2);
        }
        
        /* Chat body */
        .fb-chat-body {
            background-color: white;
            height: 500px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: height 0.3s ease;
        }
        
        .fb-chat-body.collapsed {
            height: 0;
        }
        
        /* Messages container */
        .fb-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: var(--fb-gray);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        /* Message bubbles */
        .fb-message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 18px;
            animation: fadeIn 0.2s ease-out;
            position: relative;
            word-wrap: break-word;
        }
        
        .fb-message.received {
            align-self: flex-start;
            background-color: white;
            border-top-left-radius: 5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .fb-message.sent {
            align-self: flex-end;
            background-color: var(--fb-blue);
            color: white;
            border-top-right-radius: 5px;
        }
        
        /* Typing indicator */
        .fb-typing {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: white;
            border-radius: 18px;
            align-self: flex-start;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 5px;
            animation: fadeIn 0.3s ease-out;
        }
        
        .fb-typing .dot {
            width: 8px;
            height: 8px;
            background-color: var(--fb-dark-gray);
            border-radius: 50%;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .fb-typing .dot:nth-child(1) {
            animation-delay: 0s;
        }
        
        .fb-typing .dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .fb-typing .dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        /* Input area */
        .fb-chat-input {
            display: flex;
            padding: 10px;
            background-color: white;
            border-top: 1px solid #ddd;
            align-items: center;
        }
        
        .fb-chat-input input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            background-color: var(--fb-gray);
            outline: none;
            transition: background-color 0.2s;
        }
        
        .fb-chat-input input:focus {
            background-color: #e4e6eb;
        }
        
        .fb-chat-input button {
            background: none;
            border: none;
            color: var(--fb-blue);
            font-size: 20px;
            margin-left: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .fb-chat-input button:hover {
            transform: scale(1.1);
        }
        
        /* Minimized state */
        .fb-chat-widget.minimized {
            width: 200px;
        }
        
        .fb-chat-widget.minimized .fb-chat-body {
            display: none;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes typingAnimation {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-5px);
            }
        }
        
        /* Message animations */
        .fb-message.sent {
            animation: messageSent 0.3s ease-out;
        }
        
        .fb-message.received {
            animation: messageReceived 0.3s ease-out;
        }
        
        @keyframes messageSent {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes messageReceived {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Online status */
        .online-status {
            font-size: 12px;
            color: var(--fb-dark-gray);
            margin-bottom: 10px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        .online-status .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--fb-green);
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center">Facebook Chat Widget Clone</h1>
        <p class="text-center">Click the widget in the bottom right corner to interact</p>
    </div>

    <!-- Facebook Chat Widget -->
    <div class="fb-chat-widget">
        <div class="fb-chat-header">
            <div class="title">Messenger</div>
            <div class="controls">
                <i class="fas fa-minus minimize-btn"></i>
                <i class="fas fa-times close-btn"></i>
            </div>
        </div>
        <div class="fb-chat-body">
            <div class="fb-messages">
                <div class="online-status">
                    <span class="dot"></span> Active now
                </div>
                <div class="fb-message received">
                    Hi there! How can I help you today?
                </div>
                <div class="fb-typing">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
                <div class="fb-message sent">
                    Hello! I have a question about your services.
                </div>
                <div class="fb-message received">
                    Sure, I'd be happy to help. What would you like to know?
                </div>
            </div>
            <div class="fb-chat-input">
                <input type="text" placeholder="Type a message..." class="message-input">
                <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatWidget = document.querySelector('.fb-chat-widget');
            const chatHeader = document.querySelector('.fb-chat-header');
            const chatBody = document.querySelector('.fb-chat-body');
            const minimizeBtn = document.querySelector('.minimize-btn');
            const closeBtn = document.querySelector('.close-btn');
            const sendBtn = document.querySelector('.send-btn');
            const messageInput = document.querySelector('.message-input');
            const messagesContainer = document.querySelector('.fb-messages');
            
            // Toggle chat body
            chatHeader.addEventListener('click', function(e) {
                if (!e.target.classList.contains('minimize-btn') && 
                    !e.target.classList.contains('close-btn') &&
                    !e.target.classList.contains('fa-minus') &&
                    !e.target.classList.contains('fa-times')) {
                    chatBody.classList.toggle('collapsed');
                }
            });
            
            // Minimize button
            minimizeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                chatWidget.classList.toggle('minimized');
                if (chatWidget.classList.contains('minimized')) {
                    minimizeBtn.classList.replace('fa-minus', 'fa-comment');
                } else {
                    minimizeBtn.classList.replace('fa-comment', 'fa-minus');
                }
            });
            
            // Close button
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                chatWidget.style.display = 'none';
            });
            
            // Send message
            function sendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText) {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('fb-message', 'sent');
                    messageElement.textContent = messageText;
                    messagesContainer.appendChild(messageElement);
                    messageInput.value = '';
                    
                    // Scroll to bottom
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Simulate reply after 1-2 seconds
                    setTimeout(() => {
                        // Show typing indicator
                        const typingIndicator = document.querySelector('.fb-typing');
                        typingIndicator.style.display = 'flex';
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        
                        // After 1-3 seconds, send reply
                        setTimeout(() => {
                            typingIndicator.style.display = 'none';
                            const replies = [
                                "I see. What else would you like to know?",
                                "Thanks for your message!",
                                "Let me check that for you...",
                                "That's a great question!",
                                "I'll need to look that up for you."
                            ];
                            const randomReply = replies[Math.floor(Math.random() * replies.length)];
                            
                            const replyElement = document.createElement('div');
                            replyElement.classList.add('fb-message', 'received');
                            replyElement.textContent = randomReply;
                            messagesContainer.appendChild(replyElement);
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }, 1000 + Math.random() * 2000);
                    }, 500 + Math.random() * 1500);
                }
            }
            
            sendBtn.addEventListener('click', sendMessage);
            
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>