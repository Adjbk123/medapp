<?php
require_once '../includes/session.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$prenom = $_SESSION['prenom'] ?? 'Patient';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Médical</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: transparent;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chatbot-header {
            background-color: #3b82f6;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        
        .chatbot-title {
            font-weight: 600;
            font-size: 16px;
        }
        
        .chatbot-close {
            cursor: pointer;
            font-size: 18px;
            background: none;
            border: none;
            color: white;
        }
        
        .chatbot-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #f9fafb;
        }
        
        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 5px;
            word-wrap: break-word;
        }
        
        .chatbot-message {
            background-color: #f3f4f6;
            color: #1f2937;
            align-self: flex-start;
            border-radius: 18px 18px 18px 0;
        }
        
        .user-message {
            background-color: #3b82f6;
            color: white;
            align-self: flex-end;
        }
        
        .chatbot-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
            background-color: white;
        }
        
        .chatbot-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            outline: none;
        }
        
        .chatbot-input button {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px 15px;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chatbot-input button:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="chatbot-header">
        <h3 class="chatbot-title"><i class="fas fa-robot mr-2"></i> Assistant Médical</h3>
        <button class="chatbot-close" id="chatbotClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="message chatbot-message">
            Bonjour <?php echo htmlspecialchars($prenom); ?>, je suis votre assistant médical. Comment puis-je vous aider aujourd'hui ?
        </div>
        <!-- Les messages s'afficheront ici -->
    </div>
    
    <div class="chatbot-input">
        <input type="text" id="userInput" placeholder="Posez votre question médicale ici..." autocomplete="off">
        <button id="sendButton">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatbotClose = document.getElementById('chatbotClose');
            const chatbotMessages = document.getElementById('chatbotMessages');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');

            // Fermer le chatbot
            chatbotClose.addEventListener('click', function() {
                // Envoyer un message au parent pour fermer l'iframe
                window.parent.postMessage('closeChatbot', '*');
            });

            // Fonction pour ajouter un message
            function addMessage(message, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = isUser ? 'message user-message' : 'message chatbot-message';
                messageDiv.textContent = message;
                chatbotMessages.appendChild(messageDiv);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }

            // Fonction pour envoyer un message
            function sendMessage() {
                const message = userInput.value.trim();
                if (message === '') return;

                // Ajouter le message de l'utilisateur
                addMessage(message, true);
                userInput.value = '';

                // Envoyer la requête au serveur
                fetch('../chatbot/process.php', {
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
            sendButton.addEventListener('click', sendMessage);
            
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Focus sur l'input
            userInput.focus();
        });
    </script>
</body>
</html>
