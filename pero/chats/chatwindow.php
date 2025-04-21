<?php
require ADMIN_FILES.'auth/authMiddleware.php';
$allChats = $db->table('queries')->where('executive_id', $_SESSION['user_id'])->where('status', '!=', 'closed')->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
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
            /* overflow-y: hidden; */
        }
        
        .chat-container {
            box-shadow: 0 0 30px rgba(0,0,0,0.08);
        }
        
        .sidebar {
            background-color: var(--white);
            border-right: 1px solid rgba(0,0,0,0.05);
        }
        
        .chat-area {
            background-color: var(--white);
            height: calc(100vh - 283px);
            /* flex-grow: 1; */
            overflow-y: auto;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBvcGFjaXR5PSIwLjA1Ij48cGF0dGVybiBpZD0icGF0dGVybi1iYXNlIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IGlkPSJwYXR0ZXJuLWJnIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ3aGl0ZSI+PC9yZWN0PjxjaXJjbGUgaWQ9InBhdHRlcm4tZG90IiBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0iIzAwMCI+PC9jaXJjbGU+PC9wYXR0ZXJuPjxyZWN0IGlkPSJwYXR0ZXJuLWJhc2UtcmVjdCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuLWJhc2UpIj48L3JlY3Q+PC9zdmc+');
        }
        
        .message-input {
            border-top: 1px solid rgba(0,0,0,0.05);
            /* position: absolute;
            bottom: 0;
            left: 0;
            right: 0; */
        }
        
        /* Chat list styles */
        .chat-list-item {
            padding: 15px 10px;
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

        .chat-preview {
            width: 70%;
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
            width: 70px;
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
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div id="root" class="container-fluid"></div>
            </main>
            <?php //require_once ADMIN_FILES.'layout/footer.php' ?>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
    <script src="https://unpkg.com/react@18.3.1/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/6.26.0/babel.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script>
        const allChats = <?=$allChats?>;
        console.log('all chats', allChats);
        // window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
        // window.socket.on('new_query', (data) => {
        //     console.log(data);
        // });
        // window.socket.on('executive_assigned', (data)=>{
        //     console.log(data);
            
        // });
    </script>
    <script type="text/babel">
        const App = () => {
            const messagesEndRef = React.useRef(null);
            const [isLoggedIn,setLoggedIn] = React.useState(<?=!empty($_SESSION['query_Id']) ? true : false?>);
            const [isLoginFormVisible,setLoginFormVisible] = React.useState(false);
            const [typedMessage,setTypedMessage] = React.useState('');
            const [isAgentTyping,setAgentTyping] = React.useState(false);
            const [assignedChats,setAssignedChats] = React.useState(<?=!empty($allChats) ? json_encode($allChats) : '[]'?>);
            const [activeChatIndex,setActiveChatIndex] = React.useState(0);
            const [activeChat,setActiveChat] = React.useState({});
            const [waitingQueue,setWaitingQueue] = React.useState(0);


            const queryReceived = (chat) => {
                console.log("chat reviewd", chat);
                if(chat){
                    setAssignedChats((prev) => {
                        const exists = prev.some((item) => item.query_id === chat.query_id);
                        if (exists) {
                            return prev.map((item) => {
                                if (item.query_id === chat.query_id) {
                                    return { ...item, messages: [ ...chat.chatHistory, ...(item.messages || [])] };
                                }
                                return item;
                            });
                        } else {
                            return [...prev, { ...chat, messages: [] }];
                        }
                    });
                }
            }

            const userMessageReceived = (message) => {
                console.log("Message Recieved", message);
                setAssignedChats((prev) => {
                    const chatExists = prev.some(item => item.query_id === message.query_id);
                    if(chatExists){
                        return prev.map((item,index) => {
                            if (item.query_id === message.query_id) {
                                return {
                                    ...item,
                                    messages: [...(item.messages || []), {sender: 'user', text: message.text,time: message.time}],
                                    unreadCount : index == activeChatIndex ? 0 : (item.unreadCount || 0) + 1
                                };
                            }
                            return item;
                        });
                    }
                    return prev;
                });
            }

            const handleChatHistory = (chatHistory) => {
                console.log("chat History Recieved", chatHistory);
                setAssignedChats((prev) => {
                    const updatedChats = prev.map((item) => {
                        if (chatHistory[item.query_id]) {
                            return {
                                ...item,
                                messages: [...(chatHistory[item.query_id] || []), ...(item.messages || [])],
                            };
                        }
                        return item;
                    });
                    return updatedChats;
                });
            }

            React.useEffect(() => {
                setActiveChat({...assignedChats[activeChatIndex], unreadCount : 0});
            },[assignedChats]);

            React.useEffect(() => {
                window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
                window.socket.on("connect", () => {
                    console.log("Connected to the server");
                    window.socket.emit('executive_registered',{
                        user_id: "<?=$_SESSION['user_id']?>",
                        name: "<?=$_SESSION['name']?>",
                        email: "<?=$_SESSION['email']?>",
                        preference: "<?=$_SESSION['chat_preference']?>",
                        capacity: <?=$_SESSION['max_chats']?>,
                        totalAssigned: allChats.length,
                        assignedQueries: allChats.map(chat=>chat.query_id),
                    });
                });
                return () => {
                    window.socket.off("connect");
                    window.socket.disconnect();
                }
            }, []);

            React.useEffect(() => {
                window.socket.on("new_query",queryReceived);
                window.socket.on("userMessage",userMessageReceived);
                window.socket.on("chatHistory",handleChatHistory);
                return () => {
                    window.socket.off("new_query",queryReceived);
                    window.socket.off("chatHistory",handleChatHistory);
                }
            }, []);

            React.useEffect(() => {
                if(assignedChats.length > 0){
                    console.log("Change Chat", assignedChats[activeChatIndex], {...assignedChats[activeChatIndex], unreadCount : 0});
                    // setActiveChat({...assignedChats[activeChatIndex], unreadCount : 0});
                    setAssignedChats((prev) => {
                        return prev.map((item, index) => {
                            if (index === activeChatIndex) {
                                return {
                                    ...item,
                                    unreadCount: 0,
                                };
                            }
                            return item;
                        });
                    });
                }
            }, [activeChatIndex]);

            React.useEffect(() => {
                if (messagesEndRef.current) {
                    messagesEndRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, [activeChat && activeChat.messages]);

            function formatTime(dateString) {
                console.log(dateString);

                const date = new Date(dateString);
                const now = new Date();
                const diffInMilliseconds = now - date;
                const diffInSeconds = Math.floor(diffInMilliseconds / 1000);
                const diffInMinutes = Math.floor(diffInMilliseconds / 60000);
                const diffInHours = Math.floor(diffInMilliseconds / 3600000);

                // Check if the date is today
                const isToday = date.toDateString() === now.toDateString();

                // Check if the date is yesterday
                const isYesterday = date.getDate() === now.getDate() - 1 &&
                                    date.getMonth() === now.getMonth() &&
                                    date.getFullYear() === now.getFullYear();

                // If within the last minute, show "just now"
                if (diffInSeconds < 60 && diffInSeconds >= 0) {
                    return "Just now";
                }

                // If within the last hour, show relative time (e.g., "9 minutes ago")
                if (diffInMinutes < 60 && diffInMinutes > 0) {
                    return `${diffInMinutes} min ago`;
                }

                // If within the last 24 hours but not today, show "X hours ago"
                if (diffInHours < 24 && diffInHours > 0) {
                    return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
                }

                // If the time is today, show time in hh:mm AM/PM format
                if (isToday) {
                    const hours = date.getHours();
                    const minutes = date.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    const formattedHours = hours % 12 || 12;
                    const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;
                    return `${formattedHours}:${formattedMinutes} ${ampm}`;
                }

                // If the time is yesterday, show in "d MMM yyyy" format
                if (isYesterday) {
                    const day = date.getDate();
                    const month = date.toLocaleString('default', { month: 'short' });
                    const year = date.getFullYear();
                    return `${day} ${month} ${year}`;
                }

                // For any other case, show the full date in "d MMM yyyy" format
                const day = date.getDate();
                const month = date.toLocaleString('default', { month: 'short' });
                const year = date.getFullYear();
                return `${day} ${month} ${year}`;
            };


            const sendMessage = async () => {
                if(typedMessage.trim() == ''){
                    return;
                }
                const now = new Date();
                setAssignedChats((prev) => {
                    const chatExists = prev.some(item => item.query_id === activeChat.query_id);
                    if(chatExists){
                        return prev.map(item => {
                            if (activeChat.query_id === item.query_id) {
                                return {
                                    ...item,
                                    messages: [...(item.messages || []), { sender: 'self', text: typedMessage, time : now.toISOString() }],
                                };
                            }
                            return item;
                        });
                    }
                    return prev;
                });
                window.socket.emit('executiveMessage', { sender: 'agent', text: typedMessage.trim(), query_id: activeChat.query_id, executive_id: <?=$_SESSION['user_id']?>, time: now.toISOString() });
                setTypedMessage('');
            };

            const closeChat = async () => {
                const now = new Date();
                setAssignedChats((prev) => prev.filter(item => item.query_id !== activeChat.query_id));
                try {
                    const formData = new FormData();
                    formData.append('query_id', activeChat.query_id);
                    formData.append('chats', JSON.stringify(activeChat.messages));
                    const request = await fetch(`<?=BASE_URL?>chat-api?action=closeChat`,{
                        method: 'POST',
                        body: formData
                    });
                    if (!request.ok) throw new Error('Executive Assigned API Failed');
                    const response = await request.text();
                    console.log("resposne is ", response);
                    
                } catch (error) {
                    console.log("mark chat close error ",error);
                    
                }
                window.socket.emit('closeChat', { query_id: activeChat.query_id, executive_id: <?=$_SESSION['user_id']?>, time: now.toISOString() });
            }
            
            return (
                <div className="row">
                    <div className="col-12 chat-container">
                        <div className="row h-100">
                            <div className="col-md-5 col-lg-4 col-xl-4 sidebar p-0">
                                <div className="search-box">
                                    <i className="fas fa-search"></i>
                                    <input type="text" className="form-control search-input" placeholder="Search messages..."/>
                                </div>
                                <div className="chat-list" style={{ height: "calc(100vh - 140px)", overflowY: "auto" }} >
                                    {
                                        assignedChats.map((chat,index) =>{
                                            return (
                                                <div className={`chat-list-item ${activeChatIndex == index ? "active" : ""}`} key={index} onClick={() => setActiveChatIndex(index)}>
                                                    <div className="d-flex align-items-center justify-content-between border-1 gap-3 border-primary">
                                                        <div style={{ minHeight: "48px", minWidth: "48px" }} className="d-flex align-items-center justify-content-center p-2 rounded-circle bg-dark text-white">
                                                            <span>C</span>
                                                        </div>
                                                        <div className="flex-grow-1">
                                                            <div className="d-flex justify-content-between align-items-center">
                                                                <h6 className="mb-0">{chat.name}</h6>
                                                                <small className="text-muted">{chat.messages && chat.messages.length > 0 ? formatTime(chat.messages.at(-1).time) : ''}</small>
                                                            </div>
                                                            <div className="d-flex justify-content-between align-items-center">
                                                                <span className="chat-preview">{chat.messages && chat.messages.length > 0 ? chat.messages.at(-1).text : ''}</span>
                                                                {
                                                                    chat.unreadCount && chat.unreadCount > 0 ? <span className="badge rounded-pill bg-primary ms-2">{chat.unreadCount}</span> : ''
                                                                }
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )
                                        })
                                    }
                                </div>
                            </div>

                            <div className="col-md-7 col-lg-8 col-xl-8 p-0 d-flex flex-column position-relative">
                                <div className="chat-header d-flex align-items-center gap-3">
                                    <div style={{ minHeight: "48px", minWidth: "48px" }} className="d-flex align-items-center justify-content-center p-2 rounded-circle bg-dark text-white" >
                                        <span>C</span>
                                    </div>
                                    <div className="flex-grow-1">
                                        <h5 className="mb-0">{activeChat && activeChat.name || "Select a chat"}</h5>
                                        <small className="text-muted">
                                        <span className="typing-indicator me-2 d-none">
                                            <span className="typing-dot"></span>
                                            <span className="typing-dot"></span>
                                            <span className="typing-dot"></span>
                                        </span>
                                        <span className="status-text">10:90 PM</span>
                                        </small>
                                    </div>
                                    <div className="d-flex justify-content-end align-items-end flex-column me-3">
                                        <button className="btn btn-sm d-flex justify-content-center align-items-center gap-1">
                                            <i className="fas fa-phone"></i> +91 {activeChat && activeChat.mobile || "Select a chat"}
                                        </button>
                                        <button className="btn btn-sm d-flex justify-content-center align-items-center gap-1">
                                            <i className="fa-solid fa-envelope"></i> {activeChat && activeChat.email || "Select a chat"}
                                        </button>
                                    </div>
                                </div>

                                <div className="chat-area p-4 d-flex flex-column">
                                    <div className="text-center my-3">
                                        <span className="badge bg-light text-dark fw-normal">Today</span>
                                    </div>
                                    {
                                        activeChat && activeChat.messages && activeChat.messages.map((message, index) => {
                                            if(message.sender == "user"){
                                                return  (
                                                    <div className="d-flex flex-column">
                                                        <div className="message-bubble received">
                                                            <div>{message.text}</div>
                                                            <div className="message-time mt-1">{formatTime(message.time)}</div>
                                                        </div>
                                                    </div>
                                                )
                                            }else{
                                                return (
                                                    <div className="d-flex flex-column">
                                                        <div className="message-bubble sent">
                                                            <div>{message.text}</div>
                                                            <div className="message-time mt-1 text-white-50">{formatTime(message.time)}</div>
                                                        </div>
                                                    </div>
                                                )
                                            }
                                            
                                        })
                                    }
                                    {
                                        // <div className="typing-indicator">
                                        //     <span className="typing-dot"></span>
                                        //     <span className="typing-dot"></span>
                                        //     <span className="typing-dot"></span>
                                        // </div>
                                    }
                                    <div ref={messagesEndRef}></div>
                                </div>

                                <div className="d-flex align-items-center p-4 gap-2">
                                    <textarea autoFocus onChange={(e)=>setTypedMessage(e.target.value)} onKeyDown={(e) => {if (e.key === 'Enter') { e.preventDefault(); sendMessage()}}} value={typedMessage} rows="3" type="text" className="form-control" placeholder="write a message" ></textarea>
                                    <button onClick={sendMessage} className="send-btn"><i className="fas fa-paper-plane"></i></button>
                                    <button onClick={closeChat} className="send-btn"><i className="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )
        }
        ReactDOM.createRoot(document.getElementById('root')).render(<App />);
    </script>
</body>
</html>