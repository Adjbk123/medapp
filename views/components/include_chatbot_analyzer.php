<!-- Chargement de FontAwesome si nécessaire -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Configuration Tailwind pour le chatbot -->
<script>
    if (typeof tailwind === 'undefined' || typeof tailwind.config === 'undefined') {
        if (typeof tailwind === 'undefined') {
            console.log('Tailwind CSS n\'est pas chargé. Certains styles peuvent ne pas fonctionner correctement.');
        } else {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50: '#fdf2f8',
                                100: '#fce7f3',
                                200: '#fbcfe8',
                                300: '#f9a8d4',
                                400: '#f472b6',
                                500: '#ec4899',
                                600: '#db2777',
                                700: '#be185d',
                                800: '#9d174d',
                                900: '#831843',
                            },
                            secondary: {
                                50: '#eff6ff',
                                100: '#dbeafe',
                                200: '#bfdbfe',
                                300: '#93c5fd',
                                400: '#60a5fa',
                                500: '#3b82f6',
                                600: '#2563eb',
                                700: '#1d4ed8',
                                800: '#1e40af',
                                900: '#1e3a8a',
                            }
                        }
                    }
                }
            };
        }
    }
</script>

<!-- Bouton flottant du chatbot avec infobulle -->
<div class="fixed bottom-6 right-6 group z-50">
    <button id="chatButton" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-full w-20 h-20 text-3xl shadow-xl transition-transform duration-300 flex items-center justify-center hover:scale-110 border-2 border-white">
        <i class="fas fa-stethoscope"></i>
    </button>
    <!-- Infobulle qui apparaît au survol -->
    <div class="absolute bottom-full right-0 mb-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
        <div class="bg-black text-white text-sm py-2 px-4 rounded-lg shadow-lg whitespace-nowrap">
            Assistant Médical Intelligent
            <div class="absolute bottom-0 right-6 transform translate-y-1/2 rotate-45 w-3 h-3 bg-black"></div>
        </div>
    </div>
</div>

<!-- Modal de chat -->
<div id="chatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-primary-50 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fadeInUp border border-primary-200">
        <!-- En-tête -->
        <div class="flex justify-between items-center p-4 bg-black text-white rounded-t-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-md">
                    <i class="fas fa-stethoscope text-primary-700 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold tracking-wide text-white drop-shadow-sm">Assistant Médical Intelligent</h3>
                    <p class="text-xs text-white">Votre conseiller santé personnel</p>
                </div>
            </div>
            <button id="closeChatModal" class="text-white hover:text-gray-200 transition-colors text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Zone de messages -->
        <div id="chatMessages" class="p-4 h-96 overflow-y-auto space-y-4 bg-gradient-to-br from-primary-50 via-primary-100/30 to-primary-50 scroll-smooth">
            <!-- Messages ici -->
        </div>

        <!-- Formulaire -->
        <form id="chatForm" class="p-4 bg-primary-100/50 border-t border-primary-200">
            <div class="flex gap-3">
                <input type="text" id="messageInput" class="flex-1 px-4 py-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-400 transition" placeholder="Décrivez vos symptômes..." required>
                <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-3 rounded-lg shadow-md transition duration-200">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="mt-2 text-xs text-primary-700 flex items-center bg-primary-50 p-2 rounded-lg">
                <i class="fas fa-info-circle mr-1 text-primary-600"></i>
                <span>Pour une analyse précise, décrivez vos symptômes en détail</span>
            </div>
        </form>
    </div>
</div>

<!-- Styles typing -->
<style>
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
    margin: 0 2px;
    opacity: 0.6;
}
.typing-indicator span:nth-child(1) {
    animation: bounce 1s infinite;
}
.typing-indicator span:nth-child(2) {
    animation: bounce 1s infinite 0.2s;
}
.typing-indicator span:nth-child(3) {
    animation: bounce 1s infinite 0.4s;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
</style>

<!-- Script du chatbot avec nouvelle version -->
<script src="../../assets/js/chatbot_analyzer_new.js"></script>
