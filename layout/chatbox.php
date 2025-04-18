<link rel="stylesheet" type="text/css" href="<?=BASE_URI?>assets/css/chatbox.css">
<div class="fb-chat-widget">
    <div class="fb-chat-header">
        <div class="title">Support Chat</div>
        <div class="controls">
            <i class="fas fa-minus minimize-btn"></i>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatWidget = document.querySelector('.fb-chat-widget');
        const chatHeader = document.querySelector('.fb-chat-header');
        const chatBody = document.querySelector('.fb-chat-body');
        const minimizeBtn = document.querySelector('.minimize-btn');
        const sendBtn = document.querySelector('.send-btn');
        const messageInput = document.querySelector('.message-input');
        const messagesContainer = document.querySelector('.fb-messages');
        
        // Toggle chat body
        chatHeader.addEventListener('click', function(e) {
            if (!e.target.classList.contains('minimize-btn') && 
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
        
        // Send message
        // function sendMessage() {
        //     const messageText = messageInput.value.trim();
        //     if (messageText) {
        //         const messageElement = document.createElement('div');
        //         messageElement.classList.add('fb-message', 'sent');
        //         messageElement.textContent = messageText;
        //         messagesContainer.appendChild(messageElement);
        //         messageInput.value = '';
                
        //         // Scroll to bottom
        //         messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
        //         // Simulate reply after 1-2 seconds
        //         setTimeout(() => {
        //             // Show typing indicator
        //             const typingIndicator = document.querySelector('.fb-typing');
        //             typingIndicator.style.display = 'flex';
        //             messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
        //             // After 1-3 seconds, send reply
        //             setTimeout(() => {
        //                 typingIndicator.style.display = 'none';
        //                 const replies = [
        //                     "I see. What else would you like to know?",
        //                     "Thanks for your message!",
        //                     "Let me check that for you...",
        //                     "That's a great question!",
        //                     "I'll need to look that up for you."
        //                 ];
        //                 const randomReply = replies[Math.floor(Math.random() * replies.length)];
                        
        //                 const replyElement = document.createElement('div');
        //                 replyElement.classList.add('fb-message', 'received');
        //                 replyElement.textContent = randomReply;
        //                 messagesContainer.appendChild(replyElement);
        //                 messagesContainer.scrollTop = messagesContainer.scrollHeight;
        //             }, 1000 + Math.random() * 2000);
        //         }, 500 + Math.random() * 1500);
        //     }
        // }
        
        // sendBtn.addEventListener('click', sendMessage);
        
        // messageInput.addEventListener('keypress', function(e) {
        //     if (e.key === 'Enter') {
        //         sendMessage();
        //     }
        // });
    });
</script>