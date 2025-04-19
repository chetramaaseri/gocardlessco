<link rel="stylesheet" type="text/css" href="<?=BASE_URI?>assets/css/chatbox.css">
<div class="fb-chat-widget">
    <div class="fb-chat-header">
        <div class="title">Support Chat</div>
        <div class="controls">
            <i class="fas fa-minus minimize-btn"></i>
        </div>
    </div>
    <div id="chatBody" class="fb-chat-body"></div>
</div>
<script src="https://unpkg.com/react@18.3.1/umd/react.production.min.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js" crossorigin></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/6.26.0/babel.min.js"></script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatWidget = document.querySelector('.fb-chat-widget');
        const chatHeader = document.querySelector('.fb-chat-header');
        const chatBody = document.querySelector('.fb-chat-body');
        const minimizeBtn = document.querySelector('.minimize-btn');
        const sendBtn = document.querySelector('.send-btn');
        const messageInput = document.querySelector('.message-input');
        const messagesContainer = document.querySelector('.fb-messages');
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
        
        // sendBtn.addEventListener('click', sendMessage);
        
        // messageInput.addEventListener('keypress', function(e) {
        //     if (e.key === 'Enter') {
        //         sendMessage();
        //     }
        // });
    });
</script>
<script type="text/babel">

    const App = () => {
        const messagesEndRef = React.useRef(null);
        const [isLoggedIn,setLoggedIn] = React.useState(false);
        const [isLoginFormVisible,setLoginFormVisible] = React.useState(false);
        const [typedMessage,setTypedMessage] = React.useState('');
        const [isAgentTyping,setAgentTyping] = React.useState(false);
        const [user,setUser] = React.useState({
            'name' : '',
            'mobile': '',
            'email': ''
        });

        const [messages, setMessages] = React.useState([
            { text: 'Hello!', sender: 'bot' },
            { text: 'Welcome to GoCardlessCo', sender: 'bot' },
        ]);

        React.useEffect(() => {
            if (messagesEndRef.current) {
                messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
            }
        }, [messages,isLoginFormVisible]);

        const askForUserDetails = async () => {
            setAgentTyping(true);
            await new Promise(resolve => setTimeout(resolve, 500));
            setMessages(messages => [...messages, { text: 'Please provide your name, mobile number and email address.', sender: 'bot' }]);
            await new Promise(resolve => setTimeout(resolve, 500));
            setLoginFormVisible(true);
            setAgentTyping(false);
        }

        const loginFormHandler = async (e) => {
            e.preventDefault();
            setUser({
                'name' : e.target.name.value,
                'mobile': e.target.mobile.value,
                'email': e.target.email.value
            });
            setAgentTyping(true);
            await new Promise(resolve => setTimeout(resolve, 500));
            setMessages(messages => [...messages, { text: `Thank you ${user.name}. How can I assist you today?`, sender: 'bot' }]);
            setLoginFormVisible(false);
            setAgentTyping(false);
        }

        const sendMessage = () => {
            if(typedMessage.trim() == ''){
                return;
            }
            setMessages(messages => [...messages, { text: typedMessage.trim(), sender: 'self' }]);
            setTypedMessage('');
            if(!isLoggedIn){
                askForUserDetails();
            }
        };
        
        return (
            <React.Fragment>
                <div class="fb-messages">
                    <div class="online-status">
                        <span class="dot"></span> Active now
                    </div>
                    {
                        messages.map((text,index)=>{
                            if(text.sender == 'self'){
                                return <div class="fb-message sent">{text.text}</div>
                            }else{
                                return <div class="fb-message received">{text.text}</div>
                            }
                        })
                    }
                    {
                        isLoginFormVisible && (
                            <div className="fb-message received w-100">
                                <form onSubmit={loginFormHandler}>
                                    <input 
                                        className="form-control mb-2 py-2 bg-white text-dark"
                                        type="text" 
                                        name="name"
                                        placeholder="Full Name" 
                                        value={user.name} 
                                        autofocus="true"
                                        onChange={(e) => setUser({...user, name: e.target.value})} 
                                    />
                                    <input 
                                        className="form-control mb-2 py-2 bg-white text-dark"
                                        type="email" 
                                        name="email"
                                        placeholder="Email Address" 
                                        value={user.email} 
                                        onChange={(e) => setUser({...user, email: e.target.value})}
                                    />
                                    <input 
                                        className="form-control mb-2 py-2 bg-white text-dark"
                                        type="tel" 
                                        name="mobile"
                                        placeholder="Mobile Number" 
                                        value={user.mobile} 
                                        onChange={(e) => setUser({...user, mobile: e.target.value})}
                                    />
                                    <button 
                                        className="btn btn-sm userProfileSubmitBtn btn-primary w-100 py-2 fs-14"
                                        type="submit"
                                    >
                                        Submit
                                    </button>
                                </form>
                            </div>
                        )
                    }
                    {
                        isAgentTyping && <div class="fb-typing"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>
                    }
                    
                    <div ref={messagesEndRef} />
                </div>
                <div class="fb-chat-input">
                    <input autofocus="true" onChange={(e)=>setTypedMessage(e.target.value)} onKeyDown={(e) => {if (e.key === 'Enter') {sendMessage()}}} value={typedMessage} type="text" placeholder="Type a message..." class="message-input"/>
                    <button onClick={sendMessage} class="send-btn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </React.Fragment>
        )
    }

    ReactDOM.createRoot(document.getElementById('chatBody')).render(<App />);

</script>