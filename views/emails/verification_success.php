<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Compte vérifié - MedConnect</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f7f6;">
    <div style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #e1e1e1;">
        <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">Compte Vérifié !</h1>
        </div>
        
        <div style="padding: 30px;">
            <p style="font-size: 18px; color: #2c3e50;">Bonjour <strong><?php echo htmlspecialchars($nom); ?></strong>,</p>
            
            <p>Nous avons le plaisir de vous informer que votre compte sur <strong>MedConnect</strong> a été vérifié avec succès par notre équipe administrative.</p>
            
            <p>Vous avez maintenant un accès complet à toutes les fonctionnalités de la plateforme :</p>
            
            <ul style="color: #555; padding-left: 20px;">
                <?php if ($role === 'medecin'): ?>
                    <li>Gestion de vos consultations et ordonnances</li>
                    <li>Suivi de vos dossiers patients</li>
                    <li>Messagerie sécurisée</li>
                <?php else: ?>
                    <li>Prise de rendez-vous en ligne</li>
                    <li>Accès à votre carnet de santé numérique</li>
                    <li>Consultation de vos ordonnances</li>
                <?php endif; ?>
            </ul>
            
            <div style="text-align: center; margin: 40px 0;">
                <a href="<?php echo htmlspecialchars($login_link); ?>" 
                   style="display: inline-block; background-color: #3498db; color: white; padding: 15px 35px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; transition: background-color 0.3s;">
                    Accéder à mon compte
                </a>
            </div>
            
            <p style="font-size: 14px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 20px;">
                Si vous avez des questions, n'hésitez pas à nous contacter à medapp@manosphone.com.
            </p>
            
            <p>Cordialement,<br><strong>L'équipe MedConnect</strong></p>
        </div>
        
        <div style="background-color: #f9f9f9; color: #95a5a6; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; <?php echo date('Y'); ?> MedConnect - Votre partenaire santé numérique.</p>
            <p>Ceci est un message automatique, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
