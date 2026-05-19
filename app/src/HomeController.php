<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

/**
 * Serves the single-page Vue application shell.
 *
 * In development (SS_ENVIRONMENT_TYPE=dev), the template loads assets directly
 * from the Vite dev server (port 3000) which provides HMR. In production the
 * template reads the Vite manifest (themes/app/dist/.vite/manifest.json) to
 * resolve the hashed asset filenames produced by `npm run build`.
 */
class HomeController extends Controller
{
    private static $allowed_actions = ['index'];

    public function index(HTTPRequest $request)
    {
        $isDev = Environment::getEnv('SS_ENVIRONMENT_TYPE') === 'dev';

        $data = [
            'ViteDevMode' => $isDev,
            'ViteScriptURL' => '',
            'ViteCSSFiles' => ArrayList::create(),
        ];

        if (!$isDev) {
            $assets = $this->resolveViteAssets();
            $data['ViteScriptURL'] = $assets['script'];
            $data['ViteCSSFiles'] = $assets['css'];
        }

        return $this->customise(ArrayData::create($data))->renderWith('Page');
    }

    /**
     * Reads themes/app/dist/.vite/manifest.json and returns the hashed URLs
     * for the main entry point's script and any associated CSS files.
     */
    private function resolveViteAssets(): array
    {
        $manifestPath = BASE_PATH . '/themes/app/dist/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            return ['script' => '', 'css' => ArrayList::create()];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
        $entry = $manifest['themes/app/src/js/main.js'] ?? null;

        if (!$entry) {
            return ['script' => '', 'css' => ArrayList::create()];
        }

        $base = '/themes/app/dist/';

        $cssFiles = ArrayList::create();
        foreach ($entry['css'] ?? [] as $cssFile) {
            $cssFiles->push(ArrayData::create(['URL' => $base . $cssFile]));
        }

        return [
            'script' => $base . $entry['file'],
            'css'    => $cssFiles,
        ];
    }
}
