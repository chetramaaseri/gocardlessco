<?php 
$executiveAssigned = [];
$loggedInUser = [];
if(!empty($_SESSION['query_Id'])){
    $executiveAssigned = (array) $db->table('queries')
    ->leftJoin('users', 'users.user_id', '=', 'queries.executive_id')
    ->where('queries.query_id', $_SESSION['query_Id'])
    ->select('users.*', 'queries.query_id')
    ->first();

    $loggedInUser = [
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'mobile' => $_SESSION['mobile'],
        'executive_id' => $executiveAssigned['user_id'],
        'query_id' => $executiveAssigned['query_id'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
}

?>
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
<script>
    const loggedInUser = <?=json_encode($loggedInUser)?>;
    window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
    window.socket.on("connect", () => {
        if(loggedInUser){
            window.socket.emit('user_registered', loggedInUser);
        }
        console.log("Connected to the server");
    });
</script>
<script type="text/babel">

    const App = () => {
        const messagesEndRef = React.useRef(null);
        const [isLoggedIn,setLoggedIn] = React.useState(<?=!empty($_SESSION['query_Id']) ? true : false?>);
        const [isLoginFormVisible,setLoginFormVisible] = React.useState(false);
        const [typedMessage,setTypedMessage] = React.useState('');
        const [agent,setAgent] = React.useState(<?=json_encode($executiveAssigned)?>);
        const [isAgentTyping,setAgentTyping] = React.useState(false);
        const [waitingQueue,setWaitingQueue] = React.useState(0);
        const [user,setUser] = React.useState({
            'name' : '<?=$_SESSION['name'] ?? 'chet'?>',
            'mobile': '<?=$_SESSION['mobile'] ?? '456789'?>',
            'email': '<?=$_SESSION['email'] ?? 'chet@gmail.com'?>',
            'query_id' : <?=$_SESSION['query_Id'] ?? 0?>
        });

        const [messages, setMessages] = React.useState([
            { text: `Hello${isLoggedIn ? ' '+user.name:''}!`, sender: 'bot' },
            { text: 'Welcome to GoCardlessCo', sender: 'bot' },
        ]);

        const handleExecutiveAssigned = async (executiveAssigned) => {
            if(executiveAssigned && executiveAssigned.user_id){
                setAgent(executiveAssigned);
                const formData = new FormData();
                formData.append('executive_id', executiveAssigned.user_id);
                formData.append('query_id', executiveAssigned.query_id);
                try {
                    const response = await fetch('<?=BASE_URL?>chat-api?action=executiveAssigned', {
                        method: 'POST',
                        body: formData
                    });
                    if (!response.ok) throw new Error('Executive Assigned API Failed');
                    const result = await response.json();
                } catch (error) {
                    console.error('Executive Assigned Callback error:', error);
                }
            }else{
                alert('no exetive to be assigned')
            }
        };
        const handleExecutiveNotAvailable = async (data) => {
            console.log("exe not available", data);
            
            if(data){
                setMessages(messages => [...messages, { text: 'Describe Your Query.', sender: 'bot' }]);
            }
        };
        const handleWaitingQueue = async (data) => {
            
            console.log("wait in qyeryw", data);
            if(data){
                setWaitingQueue(data.waitingUsers);
                setMessages(messages => [...messages, { text: 'Kindly Wait For a While Our Executives are Busy.', sender: 'bot' }]);
            }
        };
        React.useEffect(() => {
            window.socket.on('executive_assigned', handleExecutiveAssigned);
            return () => {
                window.socket.off('executive_assigned', handleExecutiveAssigned);
            };
        }, []);
        React.useEffect(() => {
            window.socket.on('executive_not_available', handleExecutiveNotAvailable);
            return () => {
                window.socket.off('executive_not_available', handleExecutiveNotAvailable);
            };
        }, []);
        React.useEffect(() => {
            window.socket.on('waiting_queue', handleWaitingQueue);
            return () => {
                window.socket.off('waiting_queue', handleWaitingQueue);
            };
        }, []);

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
            const formData = new FormData(e.target);
            setAgentTyping(true);
            try {
                const response = await fetch('<?=BASE_URL?>chat-api?action=registerQuery', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error('Form submission failed');
                const result = await response.json();
                setLoggedIn(true);
                window.socket.emit('user_registered', result);
                const name = formData.get('name');
                const mobile = formData.get('mobile');
                const email = formData.get('email');
                setUser({ name, mobile, email, query_id: result.query_id });
            } catch (error) {
                console.error('Login form submission error:', error);
            }
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
                <div className="fb-messages">
                    <div className="online-status">
                        <span className="dot"></span> Active now
                    </div>
                    {
                        messages.map((text,index)=>{
                            if(text.sender == 'self'){
                                return <div className="fb-message sent">{text.text}</div>
                            }else{
                                return <div className="fb-message received">{text.text}</div>
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
                        waitingQueue ?
                            <div className="fb-waiting-queue">
                                <div className="queue-header">
                                    <i className="fas fa-users me-2"></i>
                                    Waiting Queue
                                </div>
                                
                                <div className="queue-info-container">
                                    <div className="queue-position-badge">
                                        {waitingQueue}
                                    </div>
                                    
                                    <div className="queue-text-container">
                                        <div className="queue-position-text">Your position in queue</div>
                                        <div className="queue-time-estimate">
                                            {waitingQueue === 1 ? 'You are next!' : `Approx. wait time: ${Math.ceil(waitingQueue * 1.5)} minutes`}
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="queue-progress-container">
                                    <div 
                                        className="queue-progress-bar" 
                                        role="progressbar" 
                                        style={{ width: `${Math.min(100, 100 - ((waitingQueue - 1) * 10))}%` }}
                                        aria-valuenow={waitingQueue}
                                        aria-valuemin="0"
                                        aria-valuemax="10"
                                    ></div>
                                </div>
                                
                                <div className="queue-status-indicator">
                                    <div className="typing-indicator">
                                        <span className="typing-dot"></span>
                                        <span className="typing-dot delay-1"></span>
                                        <span className="typing-dot delay-2"></span>
                                    </div>
                                    <span className="queue-status-text">
                                        {waitingQueue === 1 ? 'Agent will be with you shortly' : 'Please wait patiently'}
                                    </span>
                                </div>
                            </div>
                        : <div className="no-queue"></div>
                    }
                    {
                        isAgentTyping && <div className="fb-typing"><div className="dot"></div><div className="dot"></div><div className="dot"></div></div>
                    }
                    <div ref={messagesEndRef} />
                </div>
                <div className="fb-chat-input">
                    <input autofocus="true" onChange={(e)=>setTypedMessage(e.target.value)} onKeyDown={(e) => {if (e.key === 'Enter') {sendMessage()}}} value={typedMessage} type="text" placeholder="Type a message..." className="message-input"/>
                    <button onClick={sendMessage} className="send-btn"><i className="fas fa-paper-plane"></i></button>
                </div>
            </React.Fragment>
        )
    }

    ReactDOM.createRoot(document.getElementById('chatBody')).render(<App />);

</script>