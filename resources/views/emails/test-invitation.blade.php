<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à passer votre test technique</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background-color: #4f46e5;
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }
        .content {
            padding: 40px;
        }
        .content h2 {
            margin-top: 0;
            font-size: 20px;
            color: #0f172a;
        }
        .cta-container {
            text-align: center;
            margin: 40px 0;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
        .footer {
            padding: 24px;
            text-align: center;
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 14px;
        }
        .info-box {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #475569;
        }
        .info-box strong {
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TestPlatform</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $session->candidate->first_name }},</h2>
            <p>ITIC Paris vous invite à passer une évaluation technique en ligne pour le domaine suivant :</p>
            
            <div class="info-box">
                <p><strong>Evaluation :</strong> {{ $session->template->name }}</p>
                <p><strong>Durée estimée :</strong> {{ round($session->template->duration_minutes) }} minutes</p>
                <p><strong>Domaine :</strong> {{ $session->template->domain->name }}</p>
            </div>

            <p>Une fois le test démarré, le temps commencera à s'écouler. Assurez-vous d'être dans un endroit calme et d'avoir une connexion stable.</p>

            <div class="cta-container">
                <a href="{{ route('test.start', $session->token) }}" class="btn">Commencer le test</a>
            </div>

            <p>Si le bouton ci-dessus ne fonctionne pas, vous pouvez copier et coller le lien suivant dans votre navigateur :</p>
            <p style="word-break: break-all; color: #4f46e5; font-size: 14px;">{{ route('test.start', $session->token) }}</p>
        </div>
        <div class="footer">
            <p>© 2026 ITIC Paris - Test Platform</p>
        </div>
    </div>
</body>
</html>
