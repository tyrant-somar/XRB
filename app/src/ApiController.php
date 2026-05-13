<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class ApiController extends Controller {

    private static $allowed_actions = [

        'process',

        'internal',

        'download',

        'sample'

    ];

    public function process(HTTPRequest $request) {

        if (!$request->isPOST()) {
            return $this->httpError(405);
        }

        // Get uploaded file from $_FILES
        $file = $_FILES['file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->httpError(400, 'No file uploaded');
        }

        // Read internal docx
        $internalDocxPath = BASE_PATH . '/assets/docs/XRB AI Prompt_Rules and Examples.docx';
        $internalText = $this->extractTextFromDocx($internalDocxPath);

        // Extract text from uploaded file
        $tempPath = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $userText = $this->extractTextFromUploadedFile($tempPath, $ext);

        // Combine prompts
        $prompt = $internalText . "\n\n" . $userText;

        // Call Gemini API
        $apiKey = Environment::getEnv('GEMINI_API_KEY');

        if (!$apiKey) {
            return $this->httpError(500, 'API key not set');
        }

        $response = $this->callGeminiAPI($prompt, $apiKey);

        // Return JSON
        $httpResponse = new HTTPResponse();

        $httpResponse->setBody($response);

        $httpResponse->addHeader('Content-Type', 'application/json');

        return $httpResponse;

    }

    public function sample(HTTPRequest $request) {
        $path = BASE_PATH . '/assets/docs/sample_response.json';

        if (!file_exists($path)) {
            return $this->httpError(404, 'Sample file not found');
        }

        $response = new HTTPResponse();
        $response->setBody(file_get_contents($path));
        $response->addHeader('Content-Type', 'application/json');

        return $response;
    }

    public function download(HTTPRequest $request) {
        $path = BASE_PATH . '/assets/docs/XRB AI Prompt_Rules and Examples.docx';

        if (!file_exists($path)) {
            return $this->httpError(404, 'File not found');
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="XRB AI Prompt_Rules and Examples.docx"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function internal(HTTPRequest $request) {
        $internalDocxPath = BASE_PATH . '/assets/docs/XRB AI Prompt_Rules and Examples.docx';

        $text = $this->extractTextFromDocx($internalDocxPath);

        $httpResponse = new HTTPResponse();

        $httpResponse->setBody(json_encode(['text' => $text]));

        $httpResponse->addHeader('Content-Type', 'application/json');

        return $httpResponse;

    }

    private function extractTextFromDocx($path) {

        if (!file_exists($path)) {

            return 'Internal document not found';

        }

        $phpWord = IOFactory::load($path);

        $text = '';

        foreach ($phpWord->getSections() as $section) {

            foreach ($section->getElements() as $element) {

                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {

                    foreach ($element->getElements() as $textElement) {

                        if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {

                            $text .= $textElement->getText() . ' ';

                        }

                    }

                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {

                    $text .= $element->getText() . ' ';

                }

            }

        }

        return trim($text);

    }

    private function extractTextFromUploadedFile($path, $ext) {

        if (strtolower($ext) == 'txt') {

            return file_get_contents($path);

        } elseif (strtolower($ext) == 'docx') {

            return $this->extractTextFromDocx($path);

        } elseif (strtolower($ext) == 'pdf') {

            $parser = new Parser();

            $pdf = $parser->parseFile($path);

            return $pdf->getText();

        } else {

            return 'Unsupported file type';

        }

    }

    private function callGeminiAPI($prompt, $apiKey) {

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

        $data = [

            'contents' => [

                [

                    'parts' => [

                        ['text' => $prompt]

                    ]

                ]

            ]

        ];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            'Content-Type: application/json'

        ]);

        $response = curl_exec($ch);

        return $response;

    }

}