/**
 * Chatbot Analyzer - Script pour le chatbot avec analyse de symptômes avancée
 * Ce script gère l'interface utilisateur du chatbot et les interactions avec l'API d'analyse
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du chatbot
    const chatButton = document.getElementById('chatButton');
    const chatModal = document.getElementById('chatModal');
    const closeChatModal = document.getElementById('closeChatModal');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const analyzeButton = document.getElementById('analyzeButton');

    // Vérifier si les éléments existent
    if (!chatButton || !chatModal) {
        console.error('Éléments du chatbot non trouvés');
        return;
    }

    console.log('Chatbot Analyzer initialisé');

    // Ouvrir/fermer le modal de chat
    chatButton.addEventListener('click', function() {
        chatModal.classList.remove('hidden');
        // Ajouter un message de bienvenue si le chat est vide
        if (chatMessages.children.length === 0) {
            addBotMessage("Bonjour ! Je suis votre assistant médical virtuel. Comment puis-je vous aider aujourd'hui ? Vous pouvez me décrire vos symptômes pour une analyse.");
        }
        // Focus sur le champ de saisie
        messageInput.focus();
    });

    closeChatModal.addEventListener('click', function() {
        chatModal.classList.add('hidden');
    });

    // Fonction pour ajouter un message de l'utilisateur
    function addUserMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex justify-end mb-4';
        messageDiv.innerHTML = `
            <div class="bg-gradient-to-r from-green-800 to-green-900 text-white rounded-2xl py-3 px-4 max-w-[80%] shadow-md border border-green-700">
                <p class="font-medium">${formatMessage(message)}</p>
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Fonction pour ajouter un message du bot
    function addBotMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex mb-4';
        messageDiv.innerHTML = `
            <div class="bg-white rounded-2xl py-3 px-4 max-w-[80%] shadow-md border border-gray-200">
                <p class="text-gray-800">${formatMessage(message)}</p>
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Fonction pour ajouter un message d'analyse du bot (avec style différent)
    function addAnalysisMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex mb-4';
        messageDiv.innerHTML = `
            <div class="bg-gradient-to-r from-primary-50 to-primary-100 border-l-4 border-primary-600 rounded-xl py-3 px-4 max-w-[90%] shadow-md">
                <div class="flex items-center mb-2">
                    <i class="fas fa-stethoscope text-primary-600 mr-2"></i>
                    <p class="font-semibold text-primary-800">Résultat de l'analyse :</p>
                </div>
                <p class="text-gray-800">${formatMessage(message)}</p>
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Fonction pour formater le message (gestion des sauts de ligne, etc.)
    function formatMessage(message) {
        // Remplacer les sauts de ligne par des balises <br>
        let formattedMessage = message.replace(/\n/g, '<br>');
        
        // Mettre en évidence les conseils
        if (formattedMessage.includes('En attendant, voici quelques conseils :')) {
            formattedMessage = formattedMessage.replace('En attendant, voici quelques conseils :', '<strong class="text-primary-700">En attendant, voici quelques conseils :</strong>');
        }
        
        // Mettre en évidence les alertes d'urgence
        if (formattedMessage.includes('ATTENTION:')) {
            formattedMessage = formattedMessage.replace('ATTENTION:', '<strong class="text-red-600">ATTENTION:</strong>');
            return `<div class="bg-red-50 border-l-4 border-red-500 p-2 rounded">${formattedMessage}</div>`;
        }
        
        return formattedMessage;
    }

    // Fonction pour faire défiler vers le bas
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Fonction pour envoyer un message
    function sendMessage(event) {
        if (event) {
            event.preventDefault();
        }
        
        const message = messageInput.value.trim();
        if (message === '') return;

        // Ajouter le message de l'utilisateur
        addUserMessage(message);
        
        // Vider le champ de saisie
        messageInput.value = '';
        
        // Ajouter un indicateur de chargement
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'flex mb-4 loading-message';
        loadingDiv.innerHTML = `
            <div class="bg-gray-100 rounded-lg py-2 px-4 shadow-md flex items-center">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        chatMessages.appendChild(loadingDiv);
        scrollToBottom();

        // Envoyer la requête au serveur
        fetch('../chatbot/process_advanced.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            // Supprimer l'indicateur de chargement
            const loadingMessages = document.querySelectorAll('.loading-message');
            loadingMessages.forEach(msg => msg.remove());
            
            // Déterminer si c'est un message d'analyse ou un message standard
            if (data.response.includes('Résultat de l\'analyse') || 
                data.response.includes('D\'après mon analyse') || 
                data.response.includes('En attendant, voici quelques conseils')) {
                addAnalysisMessage(data.response);
            } else {
                addBotMessage(data.response);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Supprimer l'indicateur de chargement
            const loadingMessages = document.querySelectorAll('.loading-message');
            loadingMessages.forEach(msg => msg.remove());
            
            addBotMessage('Désolé, une erreur est survenue. Veuillez réessayer plus tard.');
        });
    }

    // Événements pour envoyer un message
    chatForm.addEventListener('submit', sendMessage);
    
    // Bouton d'analyse spécifique (si présent)
    if (analyzeButton) {
        analyzeButton.addEventListener('click', function() {
            const symptomText = messageInput.value.trim();
            if (symptomText !== '') {
                sendMessage();
            } else {
                addBotMessage("Veuillez décrire vos symptômes avant de lancer l'analyse.");
            }
        });
    }
});
