<?php
$page_title = "Assistant Médical - MedConnect";
$header_title = "Assistant Médical";
$header_icon = "fas fa-robot";
include_once '../components/patient_layout_top.php';
?>

<style>
    .chatbot-container {
        height: calc(100vh - 300px);
        display: flex;
        flex-direction: column;
        background: #f8fafc;
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .message {
        max-width: 80%;
        padding: 0.8rem 1.2rem;
        border-radius: 1rem;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .chatbot-message {
        align-self: flex-start;
        background: white;
        color: #1e293b;
        border-bottom-left-radius: 0.2rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .user-message {
        align-self: flex-end;
        background: #3b82f6;
        color: white;
        border-bottom-right-radius: 0.2rem;
    }
    .chatbot-input {
        padding: 1rem 1.5rem;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 1rem;
    }
    #userInput {
        flex: 1;
        background: #f1f5f9;
        border: none;
        border-radius: 9999px;
        padding: 0.8rem 1.5rem;
        outline: none;
        transition: ring 0.3s;
    }
    #userInput:focus {
        ring: 2px solid #3b82f6;
    }
    #sendButton {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        background: #3b82f6;
        color: white;
        display: flex;
        items-center;
        justify-content: center;
        transition: transform 0.2s;
    }
    #sendButton:hover {
        transform: scale(1.1);
        background: #2563eb;
    }
</style>

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect fade-in">
    <div class="chatbot-container">
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="message chatbot-message">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-2">
                        <i class="fas fa-robot"></i>
                    </div>
                    <span class="font-bold text-sm">Assistant Médical</span>
                </div>
                Bonjour <?= htmlspecialchars($prenom) ?>, je suis votre assistant médical. Comment puis-je vous aider aujourd'hui ?
            </div>
        </div>
        
        <div class="chatbot-input">
            <input type="text" id="userInput" placeholder="Décrivez vos symptômes ou posez une question..." autocomplete="off">
            <button id="sendButton">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatbotMessages = document.getElementById('chatbotMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');

    function addMessage(message, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = isUser ? 'message user-message' : 'message chatbot-message';
        
        if (!isUser) {
            messageDiv.innerHTML = `
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-2">
                        <i class="fas fa-robot"></i>
                    </div>
                    <span class="font-bold text-sm">Assistant Médical</span>
                </div>
                ${message}
            `;
        } else {
            messageDiv.textContent = message;
        }
        
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function sendMessage() {
        const message = userInput.value.trim();
        if (message === '') return;

        addMessage(message, true);
        userInput.value = '';

        // Simuler une réponse ou appeler le process.php
        fetch('../../chatbot/process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            addMessage(data.response);
        })
        .catch(error => {
            console.error('Error:', error);
            addMessage('Désolé, une erreur est survenue. Veuillez réessayer plus tard.');
        });
    }

    sendButton.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
});
</script>

<?php include_once '../components/patient_layout_bottom.php'; ?>
