<?php
$page_title = "Messagerie - MedConnect";
$header_title = "Messagerie";
$header_icon = "fas fa-envelope";
include_once '../components/patient_layout_top.php';

require_once '../../models/Message.php';
require_once '../../includes/upload_image.php';

$database = new Database();
$db = $database->getConnection();
$message_model = new Message($db);

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $message_model->contenu = $_POST['contenu'];
    $message_model->sender_id = $id_patient;
    $message_model->receiver_id = $_POST['receiver_id'];
    $message_model->sender_type = 'patient';
    $message_model->image_url = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadMessageImage($_FILES['image']);
        if ($uploadResult['success']) {
            $message_model->image_url = $uploadResult['image_url'];
        }
    }
    
    if ($message_model->envoyer()) {
        $success = "Message envoyé.";
    }
}

// Liste des médecins
$stmt = $db->prepare("SELECT DISTINCT m.id, m.nom, m.prenom FROM medecin m JOIN rendezvous r ON m.id = r.idmedecin WHERE r.idpatient = ? ORDER BY m.nom, m.prenom");
$stmt->execute([$id_patient]);
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
$selected_medecin = isset($_GET['medecin_id']) ? (int)$_GET['medecin_id'] : ($medecins[0]['id'] ?? null);

// Récupérer les messages
$conversation = null;
if ($selected_medecin) {
    $stmt = $db->prepare("
        SELECT m.* FROM messages m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$id_patient, $selected_medecin, $selected_medecin, $id_patient]);
    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("UPDATE messages SET lu = 1 WHERE receiver_id = ? AND sender_id = ? AND sender_type = 'medecin'");
    $stmt->execute([$id_patient, $selected_medecin]);
}
?>

<style>
    .chat-wrapper {
        display: flex;
        height: calc(100vh - 160px); /* Ajusté pour tenir dans le layout */
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .contact-sidebar {
        width: 300px;
        border-right: 1px solid #edf2f7;
        display: flex;
        flex-direction: column;
    }
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
    }
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .bubble {
        max-width: 70%;
        padding: 0.8rem 1.2rem;
        border-radius: 1rem;
        position: relative;
        font-size: 0.95rem;
    }
    .bubble-me {
        align-self: flex-end;
        background: #3b82f6;
        color: white;
        border-bottom-right-radius: 0.2rem;
    }
    .bubble-them {
        align-self: flex-start;
        background: white;
        color: #1e293b;
        border-bottom-left-radius: 0.2rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .chat-input-area {
        padding: 1rem 1.5rem;
        background: white;
        border-top: 1px solid #edf2f7;
    }
</style>

<div class="chat-wrapper fade-in">
    <!-- Sidebar des contacts -->
    <div class="contact-sidebar">
        <div class="p-4 border-b">
            <h3 class="font-bold text-[#1e40af]">Conversations</h3>
        </div>
        <div class="overflow-y-auto flex-1">
            <?php foreach ($medecins as $m): ?>
                <a href="?medecin_id=<?= $m['id'] ?>" class="flex items-center p-4 hover:bg-blue-50 transition-colors <?= $selected_medecin == $m['id'] ? 'bg-blue-50 border-r-4 border-blue-500' : '' ?>">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-3">
                        <?= strtoupper(substr($m['prenom'], 0, 1) . substr($m['nom'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></p>
                        <p class="text-xs text-gray-500">Médecin traitant</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Zone de chat -->
    <div class="chat-main">
        <?php if ($selected_medecin): ?>
            <?php 
                $stmt = $db->prepare("SELECT nom, prenom FROM medecin WHERE id = ?");
                $stmt->execute([$selected_medecin]);
                $current_med = $stmt->fetch();
            ?>
            <div class="p-4 bg-white border-b flex items-center shadow-sm">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center mr-3 text-sm">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3 class="font-bold text-gray-800">Dr. <?= htmlspecialchars($current_med['prenom'] . ' ' . $current_med['nom']) ?></h3>
            </div>

            <div class="chat-messages" id="chat-box">
                <?php if ($conversation): ?>
                    <?php foreach ($conversation as $msg): 
                        $isMe = $msg['sender_type'] === 'patient';
                    ?>
                        <div class="bubble <?= $isMe ? 'bubble-me' : 'bubble-them' ?>">
                            <?= htmlspecialchars($msg['contenu']) ?>
                            <?php if ($msg['image_url']): ?>
                                <img src="<?= htmlspecialchars($msg['image_url']) ?>" class="mt-2 rounded-lg max-w-full">
                            <?php endif; ?>
                            <div class="text-[10px] mt-1 opacity-70 text-right">
                                <?= date('H:i', strtotime($msg['date_envoi'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-comments text-4xl mb-2"></i>
                        <p>Aucun message. Commencez la conversation !</p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="chat-input-area">
                <input type="hidden" name="receiver_id" value="<?= $selected_medecin ?>">
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer text-gray-400 hover:text-blue-500">
                        <i class="fas fa-image text-xl"></i>
                        <input type="file" name="image" class="hidden" accept="image/*">
                    </label>
                    <input type="text" name="contenu" placeholder="Écrivez votre message..." class="flex-1 bg-gray-100 border-none rounded-full px-6 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
                    <button type="submit" name="envoyer" class="bg-blue-500 text-white w-12 h-12 rounded-full flex items-center justify-center hover:bg-blue-600 transition-transform hover:scale-105">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                <i class="fas fa-comment-medical text-6xl mb-4"></i>
                <h2 class="text-xl font-bold">Sélectionnez une conversation</h2>
                <p>Choisissez un médecin à gauche pour discuter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

<?php include_once '../components/patient_layout_bottom.php'; ?>