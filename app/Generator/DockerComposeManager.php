<?php


namespace App\Generator;


class DockerComposeManager
{
    /**
     * @var false|string
     */
    private $originalComposes;

    private $output = ['version' => '', 'services' => [], 'networks'=>[], 'volumes'=>[]];

    public function __construct($files)
    {
        foreach ($files as $file) {
            $this->originalComposes[$file] = yaml_parse(file_get_contents($file));
            $this->output['version'] = $this->originalComposes[$file]['version'];
            if (isset($this->originalComposes[$file]['networks'])) {
                $this->output['networks'] = array_merge($this->output['networks'], $this->originalComposes[$file]['networks']);
            }
            if (isset($this->originalComposes[$file]['volumes'])) {
                $this->output['volumes'] = array_merge($this->output['volumes'], $this->originalComposes[$file]['volumes']);
            }
        }

    }

    public function locateService($serviceName)
    {
        foreach ($this->originalComposes as $originalCompose) {
            if (isset($originalCompose['services'][$serviceName])) {
                return true;
            }
        }
        return false;
    }

    public function getVersion()
    {
        return $this->output['version'];
    }

    public function collectService($service)
    {
        foreach ($this->originalComposes as $originalCompose) {
            if (!isset($originalCompose['services'][$service])) continue;
            $this->output['services'][$service] = $originalCompose['services'][$service];


            if (isset($this->output['services'][$service]['build']['context'])) {
                $this->output['services'][$service]['build']['context'] =
                    str_replace('./', './docker/', $this->output['services'][$service]['build']['context']);
            }

            if (isset($originalCompose['services'][$service]['depends_on'])) {
                foreach ($originalCompose['services'][$service]['depends_on'] as $subService) {
                    $this->collectService($subService);
                    echo 'Dependency collected: '.$subService."\n";
                }
            }
        }
    }

    public function showAddons($service) {
        if (!file_exists(app_path('../addons/'.$service))) return null;
        $addons = scandir(app_path('../addons/'.$service));
        unset($addons[0]);
        unset($addons[1]);

        if (count($addons)>0) {
            return $addons;
        } else {
            return null;
        }
    }

    public function emitNewComposeAndDockerfiles($path = '../output/docker-compose.yml')
    {

        foreach ($this->output['services'] as $name => $service) {
            foreach ($this->originalComposes as $file => $originalCompose) {
                if (!isset($originalCompose['services'][$name])) continue;
                if (file_exists((basename($file).'/'.$name))) {
                    $this->copyDirectory(app_path('../'.basename($file).'/'.$name), app_path('../output/docker/'.$name));
                }
            }
        }

        yaml_emit_file(app_path($path), $this->output);
    }

    private function copyDirectory($source, $dest)
    {
        if (!is_dir($dest) && !file_exists($dest)) mkdir($dest, 0755, true);

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            $newDestination = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (
                    !is_dir($newDestination)
                    && !file_exists($newDestination)) {
                    mkdir($newDestination);
                }
            } else {
                if (file_exists($newDestination)) {
                    unlink($newDestination);
                }
                copy($item, $newDestination);
            }
        }
    }

    public function getCollectedServices() {
        return array_keys($this->output['services']);
    }

    public function installAddon($collectedService, $addon)
    {
        $tmpAddonPath = app_path('../tmp/' . '/addons_tmp/' . $collectedService . '/' . $addon);
        $originalAddonPath = app_path('../addons/' . $collectedService . '/' . $addon);
        $this->copyDirectory($originalAddonPath, $tmpAddonPath);


        if (file_exists($tmpAddonPath.'/Dockerfile')) {
            $dockerfileAddon = file_get_contents($tmpAddonPath.'/Dockerfile');
            unlink($tmpAddonPath.'/Dockerfile');
            file_put_contents(app_path('../output/docker/'.$collectedService).'/Dockerfile', "\n\n".$dockerfileAddon, FILE_APPEND);
        }


        $this->copyDirectory($tmpAddonPath, app_path('../output/docker/'.$collectedService));

        shell_exec('rm -rf '. $tmpAddonPath);
    }
}
