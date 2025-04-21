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
        const chatHeader = document.querySelector('.fb-chat-header');
        const chatBody = document.querySelector('.fb-chat-body');
        chatHeader.addEventListener('click', function(e) {
            chatBody.classList.toggle('collapsed');
        });
    });
</script>
<script type="text/babel">
    // window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
    const loggedInUser = <?=json_encode($loggedInUser)?>;
    const App = () => {
        const messagesEndRef = React.useRef(null);
        const [isLoggedIn,setLoggedIn] = React.useState(<?=!empty($_SESSION['query_Id']) ? true : false?>);
        const [isLoginFormVisible,setLoginFormVisible] = React.useState(false);
        const [typedMessage,setTypedMessage] = React.useState('');
        const [agent,setAgent] = React.useState(<?=json_encode($executiveAssigned)?>);
        const [isAgentAvailable, setAgentAvailable] = React.useState(false);
        const [isAgentTyping,setAgentTyping] = React.useState(false);
        const [waitingQueue,setWaitingQueue] = React.useState(0);
        const [user,setUser] = React.useState({
            'name' : '<?=$_SESSION['name'] ?? 'chet'?>',
            'mobile': '<?=$_SESSION['mobile'] ?? '456789'?>',
            'email': '<?=$_SESSION['email'] ?? 'chet@gmail.com'?>',
            'query_id' : <?=$_SESSION['query_Id'] ?? 0?>
        });

        const [messages, setMessages] = React.useState([
            { text: `Hello${isLoggedIn ? ' '+user.name:''}!`, sender: 'agent' },
            { text: 'Welcome to GoCardlessCo', sender: 'agent' },
        ]);

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInMilliseconds = now - date;
            const diffInMinutes = Math.floor(diffInMilliseconds / 60000);
            const diffInHours = Math.floor(diffInMilliseconds / 3600000);

            // Check if the date is today
            const isToday = date.toDateString() === now.toDateString();
            const isYesterday = date.getDate() === now.getDate() - 1 && date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();

            // If within the last hour, show relative time (e.g., "9 minutes ago")
            if (diffInMinutes < 60 && diffInMinutes > 0) {
                return `${diffInMinutes} min ago`;
            }
            // If the time is today, show in 11:00 AM format
            if (isToday) {
                const hours = date.getHours();
                const minutes = date.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const formattedHours = hours % 12 || 12;
                const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;
                return `${formattedHours}:${formattedMinutes} ${ampm}`;
            }
            // If the time is yesterday, show in "10 Apr 2025" format
            if (isYesterday) {
                const day = date.getDate();
                const month = date.toLocaleString('default', { month: 'short' });
                const year = date.getFullYear();
                return `${day} ${month} ${year}`;
            }

            // For any other case, show the full date
            const day = date.getDate();
            const month = date.toLocaleString('default', { month: 'short' });
            const year = date.getFullYear();
            return `${day} ${month} ${year}`;
        }

        const handleExecutiveAssigned = async (executiveAssigned) => {
            console.log("executiveAssigned",executiveAssigned);
            if(executiveAssigned && executiveAssigned.user_id && executiveAssigned.query_id){
                console.log("assigned ",executiveAssigned);
                setAgentAvailable(true);
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
                    console.log('Executive Assigned API result:', result);
                    
                } catch (error) {
                    console.error('Executive Assigned Callback error:', error);
                }
            }
        };
        const handleExecutiveNotAvailable = async (data) => {
            console.log("exe not available", data);
            setAgentAvailable(false);
        };
        const handleWaitingQueue = async (data) => {
            console.log("wait in qyeryw", data);
            if(data){
                setWaitingQueue(data.waitingUsers);
                setAgentAvailable(false);
                setMessages(messages => [...messages, { text: 'Kindly Wait For a While Our Executives are Busy.', sender: 'agent' }]);
            }
        };
        const handleAgentMessage = async (data) => {
            console.log("agent message", data);
            setMessages(messages => [...messages, { text: data.text, sender: 'agent' }]);
        };
        const handleChatHistory = async (chatHistory) => {
            console.log("chat history", chatHistory);
            setMessages(messages => [ ...messages, ...chatHistory]);
        };
        const handleAgentTyping = async (data) => {
            console.log("exe typing", data);
        }
        React.useEffect(() => {
            window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
            window.socket.on("connect", () => {
                if (loggedInUser && Object.keys(loggedInUser).length > 0) {
                    console.log("logend user and register", loggedInUser);
                    window.socket.emit('user_registered', loggedInUser);
                }
                console.log("Connected to the server");
            });
            return () => {
                window.socket.off("connect");
                window.socket.disconnect();
            };
        }, []);
        React.useEffect(() => {
            window.socket.on('executive_assigned', handleExecutiveAssigned);
            window.socket.on('executive_not_available', handleExecutiveNotAvailable);
            window.socket.on('waiting_queue', handleWaitingQueue);
            window.socket.on('executiveMessage', handleAgentMessage);
            window.socket.on('chatHistory', handleChatHistory);
            return () => {
                window.socket.off('executive_assigned', handleExecutiveAssigned);
                window.socket.off('executive_not_available', handleExecutiveNotAvailable);
                window.socket.off('waiting_queue', handleWaitingQueue);
                window.socket.off('executiveMessage', handleAgentMessage);
                window.socket.off('chatHistory', handleChatHistory);
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
            setMessages(messages => [...messages, { text: 'Please provide your name, mobile number and email address.', sender: 'agent' }]);
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
            setMessages(messages => [...messages, { text: `Thank you ${user.name}. How can I assist you today?`, sender: 'agent' }]);
            setLoginFormVisible(false);
            setAgentTyping(false);
        }

        const sendMessage = async () => {
            if(typedMessage.trim() == ''){
                return;
            }
            setMessages(messages => [...messages, { text: typedMessage.trim(), sender: 'self' }]);
            if(!isLoggedIn){
                askForUserDetails();
            }else if(!isAgentAvailable){
                setAgentTyping(true);
                await new Promise(resolve => setTimeout(resolve, 500));
                setMessages(messages => [...messages, { text: 'No agent available right now.', sender: 'agent' }]);
                await new Promise(resolve => setTimeout(resolve, 500));
                setMessages(messages => [...messages, { text: 'we will get back to you.', sender: 'agent' }]);
                setAgentTyping(false);
            }else{
                const now = new Date();
                window.socket.emit('userMessage', { sender: 'user', text: typedMessage.trim(), query_id: user.query_id, executive_id: agent.user_id, time: now.toISOString()});
            }
            setTypedMessage('');
        };
        
        return (
            <React.Fragment>
                <div className="fb-messages">
                    <div className="online-status">
                        <span className="dot"></span> Active now
                    </div>
                    {
                        messages.map((text,index)=>{
                            if(text.sender == 'agent'){
                                return <div className="fb-message received">{text.text}</div>
                            }else{
                                return <div className="fb-message sent">{text.text}</div>
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