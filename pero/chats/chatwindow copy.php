<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Admin Chat UI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6C5CE7;
            --secondary-color: #A29BFE;
            --light-bg: #F8F9FA;
            --dark-text: #2D3436;
            --light-text: #636E72;
            --white: #FFFFFF;
            --online: #00B894;
            --offline: #DFE6E9;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .chat-container {
            height: 100vh;
            box-shadow: 0 0 30px rgba(0,0,0,0.08);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .sidebar {
            background-color: var(--white);
            height: 100%;
            border-right: 1px solid rgba(0,0,0,0.05);
        }
        
        .chat-area {
            background-color: var(--white);
            height: calc(100vh - 140px);
            overflow-y: auto;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBvcGFjaXR5PSIwLjA1Ij48cGF0dGVybiBpZD0icGF0dGVybi1iYXNlIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IGlkPSJwYXR0ZXJuLWJnIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ3aGl0ZSI+PC9yZWN0PjxjaXJjbGUgaWQ9InBhdHRlcm4tZG90IiBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0iIzAwMCI+PC9jaXJjbGU+PC9wYXR0ZXJuPjxyZWN0IGlkPSJwYXR0ZXJuLWJhc2UtcmVjdCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuLWJhc2UpIj48L3JlY3Q+PC9zdmc+');
        }
        
        .message-input {
            height: 140px;
            background-color: var(--white);
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        /* Chat list styles */
        .chat-list-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .chat-list-item:hover {
            background-color: rgba(108, 92, 231, 0.05);
        }
        
        .chat-list-item.active {
            background-color: rgba(108, 92, 231, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .chat-list-item.unread .chat-preview {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
        }
        
        .status-badge {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--white);
            position: absolute;
            bottom: -2px;
            right: -2px;
        }
        
        .online {
            background-color: var(--online);
        }
        
        .offline {
            background-color: var(--offline);
        }
        
        /* Message styles */
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 8px;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }
        
        .received {
            background-color: #F1F1F1;
            color: var(--dark-text);
            align-self: flex-start;
            border-top-left-radius: 4px;
        }
        
        .sent {
            background-color: var(--primary-color);
            color: var(--white);
            align-self: flex-end;
            border-top-right-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: var(--light-text);
            margin-top: 4px;
        }
        
        /* Typing indicator */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            background-color: #F1F1F1;
            padding: 10px 16px;
            border-radius: 18px;
            border-top-left-radius: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: var(--light-text);
            border-radius: 50%;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        /* Search box */
        .search-box {
            position: relative;
            padding: 20px;
            background-color: var(--white);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .search-box i {
            position: absolute;
            left: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 12px;
            border: none;
            background-color: #F1F1F1;
            height: 40px;
        }
        
        .search-input:focus {
            box-shadow: none;
            background-color: #E9E9E9;
        }
        
        /* Chat header */
        .chat-header {
            padding: 15px 20px;
            background-color: var(--white);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        /* Animations */
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-3px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.03);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.2);
        }
        
        /* Message input */
        .message-textarea {
            border: none;
            resize: none;
            padding: 15px;
            background-color: transparent;
        }
        
        .message-textarea:focus {
            outline: none;
            box-shadow: none;
        }
        
        .send-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            transition: all 0.2s;
        }
        
        .send-btn:hover {
            background-color: #5D4BDB;
            transform: translateY(-2px);
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            background-color: transparent;
            color: var(--light-text);
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background-color: #F1F1F1;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid h-100">
        <div class="row h-100 justify-content-center py-4">
            <div class="col-12 col-lg-10 col-xl-8 chat-container">
                <div class="row h-100">
                    <!-- Sidebar -->
                    <div class="col-md-5 col-lg-4 col-xl-4 sidebar p-0">
                        <!-- Search Box -->
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search messages...">
                        </div>
                        
                        <!-- Chat List -->
                        <div class="chat-list" style="height: calc(100vh - 81px); overflow-y: auto;">
                            <!-- Active Chat Item -->
                            <div class="chat-list-item active">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="https://randomuser.me/api/portraits/women/44.jpg" class="user-avatar">
                                        <span class="status-badge online"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Sarah Johnson</h6>
                                            <small class="text-muted">10:30 AM</small>
                                        </div>
                                        <p class="mb-0 chat-preview text-truncate">Hey, I have a question about the new features</p>
                                    </div>
                                    <span class="badge rounded-pill bg-primary ms-2">3</span>
                                </div>
                            </div>
                            
                            <!-- Other Chat Items -->
                            <div class="chat-list-item unread">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="user-avatar">
                                        <span class="status-badge online"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Michael Chen</h6>
                                            <small class="text-muted">9:45 AM</small>
                                        </div>
                                        <p class="mb-0 chat-preview text-truncate">The dashboard needs some adjustments</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-list-item">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="https://randomuser.me/api/portraits/women/68.jpg" class="user-avatar">
                                        <span class="status-badge offline"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Emily Wilson</h6>
                                            <small class="text-muted">Yesterday</small>
                                        </div>
                                        <p class="mb-0 text-muted text-truncate">Thanks for your help!</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- More chat items -->
                            <div class="chat-list-item">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="https://randomuser.me/api/portraits/men/75.jpg" class="user-avatar">
                                        <span class="status-badge online"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">David Kim</h6>
                                            <small class="text-muted">Yesterday</small>
                                        </div>
                                        <p class="mb-0 text-muted text-truncate">Let's schedule a meeting</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-list-item">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="https://randomuser.me/api/portraits/women/23.jpg" class="user-avatar">
                                        <span class="status-badge offline"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Jessica Parker</h6>
                                            <small class="text-muted">Jul 12</small>
                                        </div>
                                        <p class="mb-0 text-muted text-truncate">The documents have been sent</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Area -->
                    <div class="col-md-7 col-lg-8 col-xl-8 p-0 d-flex flex-column">
                        <!-- Chat Header -->
                        <div class="chat-header d-flex align-items-center">
                            <div class="position-relative me-3 ms-3">
                                <img src="https://randomuser.me/api/portraits/women/44.jpg" class="user-avatar">
                                <span class="status-badge online"></span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0">Sarah Johnson</h5>
                                <small class="text-muted">
                                    <span class="typing-indicator me-2 d-none">
                                        <span class="typing-dot"></span>
                                        <span class="typing-dot"></span>
                                        <span class="typing-dot"></span>
                                    </span>
                                    <span class="status-text">Active now</span>
                                </small>
                            </div>
                            <div class="d-flex me-3">
                                <button class="action-btn me-2">
                                    <i class="fas fa-phone"></i>
                                </button>
                                <button class="action-btn">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Messages Area -->
                        <div class="chat-area p-4 d-flex flex-column">
                            <!-- Date Divider -->
                            <div class="text-center my-3">
                                <span class="badge bg-light text-dark fw-normal">Today</span>
                            </div>
                            
                            <!-- Received Message -->
                            <div class="d-flex flex-column">
                                <div class="message-bubble received">
                                    <div>Hey there! I wanted to ask about the new dashboard features you mentioned in the meeting.</div>
                                    <div class="message-time mt-1">10:30 AM</div>
                                </div>
                            </div>
                            
                            <!-- Sent Message -->
                            <div class="d-flex flex-column">
                                <div class="message-bubble sent">
                                    <div>Hi Sarah! Yes, we're rolling out the new analytics dashboard next week. What would you like to know?</div>
                                    <div class="message-time mt-1 text-white-50">10:32 AM</div>
                                </div>
                            </div>
                            
                            <!-- Received Message -->
                            <div class="d-flex flex-column">
                                <div class="message-bubble received">
                                    <div>Will it include the real-time data visualization we discussed? That would be super helpful for our team.</div>
                                    <div class="message-time mt-1">10:33 AM</div>
                                </div>
                            </div>
                            
                            <!-- Typing Indicator -->
                            <div class="d-flex flex-column typing-indicator-container">
                                <div class="typing-indicator">
                                    <span class="typing-dot"></span>
                                    <span class="typing-dot"></span>
                                    <span class="typing-dot"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="message-input d-flex flex-column p-3">
                            <textarea class="message-textarea flex-grow-1 mb-2" placeholder="Type your message..."></textarea>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex">
                                    <button class="action-btn me-2">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <button class="action-btn">
                                        <i class="fas fa-smile"></i>
                                    </button>
                                </div>
                                <button class="send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chat list item selection
            const chatItems = document.querySelectorAll('.chat-list-item');
            chatItems.forEach(item => {
                item.addEventListener('click', function() {
                    chatItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update chat header with selected user
                    const userName = this.querySelector('h6').textContent;
                    document.querySelector('.chat-header h5').textContent = userName;
                    
                    // Simulate loading new chat
                    document.querySelector('.typing-indicator-container').classList.add('d-none');
                });
            });
            
            // Typing indicator simulation
            const messageTextarea = document.querySelector('.message-textarea');
            messageTextarea.addEventListener('focus', function() {
                document.querySelector('.chat-header .typing-indicator').classList.remove('d-none');
                document.querySelector('.chat-header .status-text').classList.add('d-none');
            });
            
            messageTextarea.addEventListener('blur', function() {
                document.querySelector('.chat-header .typing-indicator').classList.add('d-none');
                document.querySelector('.chat-header .status-text').classList.remove('d-none');
            });
            
            // Send message functionality
            const sendBtn = document.querySelector('.send-btn');
            sendBtn.addEventListener('click', function() {
                const message = messageTextarea.value.trim();
                if (message) {
                    // Create new sent message
                    const messagesContainer = document.querySelector('.chat-area');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'd-flex flex-column';
                    messageDiv.innerHTML = `
                        <div class="message-bubble sent">
                            <div>${message}</div>
                            <div class="message-time mt-1 text-white-50">Just now</div>
                        </div>
                    `;
                    messagesContainer.appendChild(messageDiv);
                    
                    // Clear input
                    messageTextarea.value = '';
                    
                    // Scroll to bottom
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Show typing indicator and simulate reply after 1-2 seconds
                    document.querySelector('.typing-indicator-container').classList.remove('d-none');
                    
                    setTimeout(() => {
                        document.querySelector('.typing-indicator-container').classList.add('d-none');
                        
                        // Add received message
                        const replies = [
                            "I'll check on that and get back to you.",
                            "Yes, that feature is included!",
                            "We can schedule a demo if you'd like.",
                            "Let me confirm with the team first.",
                            "Great question! The real-time updates will be available."
                        ];
                        const randomReply = replies[Math.floor(Math.random() * replies.length)];
                        
                        const replyDiv = document.createElement('div');
                        replyDiv.className = 'd-flex flex-column';
                        replyDiv.innerHTML = `
                            <div class="message-bubble received">
                                <div>${randomReply}</div>
                                <div class="message-time mt-1">Just now</div>
                            </div>
                        `;
                        messagesContainer.appendChild(replyDiv);
                        
                        // Scroll to bottom
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }, 1000 + Math.random() * 2000);
                }
            });
            
            // Allow sending message with Enter key (Shift+Enter for new line)
            messageTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendBtn.click();
                }
            });
        });
    </script>
</body>
</html>