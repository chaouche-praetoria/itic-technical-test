<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre évaluation technique ITIC Paris</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; border-radius: 0 !important; }
            .content { padding: 30px 20px !important; }
            .header { border-radius: 0 !important; padding: 30px 20px !important; }
            .info-card { padding: 25px 20px !important; }
        }
    </style>
</head>
<body style="background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="container" style="background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 48px 40px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <div style="background-color: rgba(255, 255, 255, 0.1); width: 48px; height: 48px; border-radius: 12px; margin-bottom: 24px; line-height: 48px; color: #ffffff; font-weight: 900; font-size: 20px;">
                                            TA
                                        </div>
                                        <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.025em;">TechAssess Framework</h1>
                                        <p style="color: #94a3b8; margin: 8px 0 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">ITIC Paris Assessment Service</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="content" style="padding: 48px 60px;">
                            <h2 style="color: #0f172a; font-size: 24px; font-weight: 800; margin: 0 0 16px; letter-spacing: -0.025em;">Bonjour {{ $session->candidate->first_name }},</h2>
                            <p style="color: #475569; font-size: 16px; line-height: 26px; margin: 0 0 32px;">
                                Vous avez été sélectionné pour passer une évaluation technique. Ce test nous aidera à mieux comprendre vos compétences et votre approche de résolution de problèmes.
                            </p>

                            <!-- Info Card -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="info-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding-bottom: 24px;">
                                        <p style="color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px;">Votre Évaluation</p>
                                        <div style="display: flex; align-items: center;">
                                            <span style="color: #0f172a; font-size: 18px; font-weight: 700;">{{ $session->template->name }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td width="50%" style="padding-right: 10px;">
                                                    <p style="color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 4px;">Durée estimée</p>
                                                    <p style="color: #475569; font-size: 14px; font-weight: 700;">{{ round($session->template->duration_minutes) }} minutes</p>
                                                </td>
                                                <td width="50%">
                                                    <p style="color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 4px;">Domaine</p>
                                                    <p style="color: #4f46e5; font-size: 14px; font-weight: 700;">{{ $session->template->domain->name }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 14px; line-height: 22px; margin: 0 0 32px; font-style: italic;">
                                <strong>Note :</strong> Une fois le test lancé, le compte à rebours commencera immédiatement. Assurez-vous d'avoir une plage horaire ininterrompue et une connexion internet stable.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('test.start', $session->token) }}" style="background-color: #4f46e5; border-radius: 16px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: 700; line-height: 56px; text-align: center; text-decoration: none; width: 280px; -webkit-text-size-adjust: none; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);">
                                            Lancer l'évaluation &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 32px 60px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0 0 8px;">Détails de sécurité : Lien à usage unique, valide pendant 48 heures.</p>
                            <p style="color: #64748b; font-size: 12px; font-weight: 700; margin: 0 0 4px;">© 2026 ITIC Paris Tech Assessment</p>
                            <p style="color: #64748b; font-size: 11px; margin: 0;">Besoin d'aide ? <a href="mailto:support@iticparis.com" style="color: #4f46e5; text-decoration: none;">Contactez le support technique</a></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Direct Link for safety -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="margin-top: 24px;">
                    <tr>
                        <td align="center">
                            <p style="color: #94a3b8; font-size: 11px; line-height: 18px; margin: 0;">
                                Si le bouton ne fonctionne pas, copiez ce lien :<br>
                                <a href="{{ route('test.start', $session->token) }}" style="color: #4f46e5; text-decoration: underline; word-break: break-all;">
                                    {{ route('test.start', $session->token) }}
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
