<?php


namespace App\Generator;


use Illuminate\Support\Facades\Storage;

class LaradockDownloader
{
    /**
     * @param string $path
     * @return string
     */
    private function downloadRepo($path)
    {
        shell_exec('git clone https://github.com/Laradock/laradock.git ' . $path);
        return $path;
    }

    /**
     * @param $path
     * @param $version
     * @return mixed
     */
    private function setVersion($path, $version)
    {
        shell_exec('cd ' . $path . ' && git reset --hard ' . $version);
        return $path;
    }

    /**
     * @param $path
     * @return mixed
     */
    private function removeGitSubDir($path)
    {
        shell_exec(' rm -rf ' . $path . '/.github && rm -rf ' . $path . '/.git');
        return $path;
    }

    /**
     * @param $version
     * @param string $path
     */
    public function download($version, $path = 'tmp/laradock')
    {
        $this->downloadRepo($path);
        $this->setVersion($path, $version);
        $this->removeGitSubDir($path);

    }
}
