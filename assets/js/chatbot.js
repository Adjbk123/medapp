document.addEventListener('DOMContentLoaded', function() {
    // Éléments du chatbot
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatBubbleContainer = document.getElementById('chatBubbleContainer');
    const chatBubbleClose = document.getElementById('chatBubbleClose');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');

    // Vérifier si les éléments existent
    if (!chatToggleBtn || !chatBubbleContainer) {
        console.error('Éléments du chatbot non trouvés');
        return;
    }

    console.log('Chatbot initialisé');

    // Toggle chat bubble visibility
    chatToggleBtn.addEventListener('click', function() {
        console.log('Bouton chatbot cliqué');
        chatBubbleContainer.classList.toggle('active');
    });

    // Close chat bubble
    if (chatBubbleClose) {
        chatBubbleClose.addEventListener('click', function() {
            console.log('Fermeture du chatbot');
            chatBubbleContainer.classList.remove('active');
        });
    }

    // Fonction pour ajouter un message
    function addMessage(message, isUser = false) {
        if (!chatbotMessages) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = isUser ? 'message user-message' : 'message chatbot-message';
        messageDiv.textContent = message;
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    // Fonction pour envoyer un message
    function sendMessage() {
        if (!userInput || !sendButton) return;
        
        const message = userInput.value.trim();
        if (message === '') return;

        // Ajouter le message de l'utilisateur
        addMessage(message, true);
        userInput.value = '';

        // Envoyer la requête au serveur
        fetch('../../chatbot/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            // Ajouter la réponse du chatbot
            addMessage(data.response);
        })
        .catch(error => {
            console.error('Error:', error);
            addMessage('Désolé, une erreur est survenue. Veuillez réessayer plus tard.');
        });
    }

    // Événements pour envoyer un message
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    if (userInput) {
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
});
