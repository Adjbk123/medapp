<!-- Bouton flottant du chatbot -->
<button id="chatButton"
    class="fixed bottom-6 right-6 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white rounded-full w-16 h-16 text-3xl shadow-xl transition-transform duration-300 flex items-center justify-center z-50 hover:scale-110">
    <i class="fas fa-stethoscope"></i>
</button>

<!-- Fenêtre modale du chatbot -->
<div id="chatModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fadeInUp">
        
        <!-- En-tête -->
        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-primary-500 to-primary-600 text-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-inner">
                    <i class="fas fa-stethoscope text-primary-600 text-lg"></i>
                </div>
                <h3 class="text-xl font-semibold">Assistant Médical Intelligent</h3>
            </div>
            <button id="closeChatModal"
                class="text-white hover:text-gray-200 transition-colors text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages -->
        <div id="chatMessages"
            class="p-4 h-96 overflow-y-auto space-y-4 bg-gradient-to-br from-white via-gray-50 to-white scroll-smooth">
            <!-- Messages dynamiques ici -->
        </div>

        <!-- Champ de saisie -->
        <form id="chatForm" class="p-4 bg-gray-50 border-t border-gray-200">
            <div class="flex gap-3">
                <input type="text" id="messageInput"
                    class="flex-1 px-4 py-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-400 transition"
                    placeholder="Décrivez vos symptômes..." required>
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-3 rounded-lg shadow-md transition duration-200">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="mt-2 text-xs text-gray-500 flex items-center">
                <i class="fas fa-info-circle mr-1"></i>
                Pour une analyse précise, décrivez vos symptômes en détail
            </div>
        </form>
    </div>
</div>

<!-- Animations & Typing -->
<style>
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    .typing-indicator {
        display: flex;
        align-items: center;
        justify-content: start;
        padding: 0.5rem;
        gap: 4px;
    }

    .typing-indicator span {
        height: 8px;
        width: 8px;
        background: var(--primary-color, #22c55e);
        border-radius: 50%;
        display: inline-block;
        animation: bounce 1s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes fadeInUp {
        0% {
            transform: translateY(20px);
            opacity: 0;
        }

        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.4s ease-out;
    }
</style>

<!-- Script JS -->
<script src="../../assets/js/chatbot_analyzer.js"></script>
