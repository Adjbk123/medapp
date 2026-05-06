<?php
$page_title = "Messagerie - MedConnect";
$header_title = "Messagerie";
$header_icon = "fas fa-envelope";
include_once '../components/doctor_layout_top.php';
require_once '../../models/Message.php';
require_once '../../includes/upload_image.php';

$message = new Message($db);

// Récupérer les patients
$stmt = $db->prepare("SELECT id, nom, prenom FROM patient WHERE id_medecin = ? ORDER BY nom, prenom");
$stmt->execute([$user_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $message->contenu = $_POST['contenu'];
    $message->sender_id = $user_id;
    $message->receiver_id = $_POST['receiver_id'];
    $message->sender_type = 'medecin';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadMessageImage($_FILES['image']);
        if ($upload['success']) $message->image_url = $upload['image_url'];
    }
    
    if ($message->envoyer()) $success = "Message envoyé.";
}

$selected_patient_id = $_GET['patient_id'] ?? null;
$conversation = [];
if ($selected_patient_id) {
    $stmt = $message->getConversation($user_id, $selected_patient_id);
    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($conversation as $msg) {
        if ($msg['receiver_id'] == $user_id && $msg['lu'] == 0) $message->marquerCommeLu($msg['id']);
    }
}
?>

<div class="bg-white rounded-2xl shadow-lg border border-gray-100 flex overflow-hidden h-[calc(100vh-180px)] fade-in">
    <!-- Liste des discussions -->
    <div class="w-1/3 border-r flex flex-col">
        <div class="p-6 border-b bg-gray-50">
            <h3 class="font-bold text-gray-800">Patients</h3>
        </div>
        <div class="flex-1 overflow-y-auto">
            <?php foreach ($patients as $p): ?>
                <a href="?patient_id=<?= $p['id'] ?>" class="flex items-center gap-4 p-4 hover:bg-green-50 transition-colors <?= $selected_patient_id == $p['id'] ? 'bg-green-50 border-r-4 border-green-500' : '' ?>">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500">
                        <?= strtoupper(substr($p['prenom'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-800 truncate"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Zone de chat -->
    <div class="flex-1 flex flex-col bg-gray-50">
        <?php if ($selected_patient_id): ?>
            <div class="p-4 border-b bg-white flex items-center gap-4 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold">
                    <i class="fas fa-user"></i>
                </div>
                <h3 class="font-bold text-gray-800">Discussion</h3>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4" id="chat-box">
                <?php foreach ($conversation as $msg): ?>
                    <div class="flex <?= $msg['sender_id'] == $user_id ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[70%] p-4 rounded-2xl shadow-sm <?= $msg['sender_id'] == $user_id ? 'bg-green-600 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none' ?>">
                            <?php if ($msg['image_url']): ?>
                                <img src="<?= htmlspecialchars($msg['image_url']) ?>" class="rounded-lg mb-2 max-w-full">
                            <?php endif; ?>
                            <p class="text-sm"><?= htmlspecialchars($msg['contenu']) ?></p>
                            <p class="text-[10px] mt-1 opacity-70 text-right"><?= date('H:i', strtotime($msg['date_envoi'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-4 bg-white border-t flex items-end gap-3">
                <input type="hidden" name="receiver_id" value="<?= $selected_patient_id ?>">
                <div class="flex-1 bg-gray-100 rounded-2xl p-2 flex items-center">
                    <textarea name="contenu" rows="1" class="flex-1 bg-transparent border-none focus:ring-0 px-3 py-1 resize-none" placeholder="Votre message..."></textarea>
                    <label class="p-2 cursor-pointer text-gray-400 hover:text-green-600">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" name="image" class="hidden">
                    </label>
                </div>
                <button type="submit" name="envoyer" class="w-12 h-12 rounded-full bg-green-600 text-white flex items-center justify-center shadow-lg hover:bg-green-700 transition-transform active:scale-95">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-comments text-6xl mb-4 opacity-20"></i>
                <p>Sélectionnez une discussion pour commencer</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include_once '../components/doctor_layout_bottom.php'; ?>