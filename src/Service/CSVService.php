<?php

namespace App\Service;

class CSVService {

    private string $csvDir;

    public function __construct(
        string $projectDir
    ) {
        $this->csvDir = $projectDir.'/var/data/';
    }

    public function readCSVData(string $filename): array {
        $path = $this->csvDir.$filename;

        if (!file_exists($path))
            throw new \RuntimeException("File $filename doesn't exists!");

        if (($handle = fopen($path, 'r')) === false)
            throw new \RuntimeException("Error during opening file $filename!");
        
        $data = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        fclose($handle);

        return $data;
    }

    public function writeCSVData(string $filename, array $data): void {
        $path = $this->csvDir.$filename;
        
        if (!file_exists($path)) 
            throw new \RuntimeException("File $filename doesn't exists!");

        if (($handle = fopen($path, 'w')) === false)
            throw new \RuntimeException("Error during opening file $filename!");
        
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
    }

    public function appendCSVData(string $filename, array $data): void {
        $path = $this->csvDir.$filename;

        if (!file_exists($path)) 
            throw new \RuntimeException("File $filename doesn't exists!");
        
        if (($handle = fopen($path, 'a')) === false)
            throw new \RuntimeException("Error during opening file $filename!");

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

}