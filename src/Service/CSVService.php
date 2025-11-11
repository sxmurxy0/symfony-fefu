<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOException;

class CSVService {

    public function __construct(
        private string $csvDir
    ) {}

    public function readCSVData(string $filename): array {
        $path = $this->csvDir.$filename;

        if (!file_exists($path))
            throw new IOException("File $filename doesn't exists!");

        if (($handle = fopen($path, 'r')) === false)
            throw new IOException("Error during opening file $filename!");
        
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
            throw new IOException("File $filename doesn't exists!");

        if (($handle = fopen($path, 'w')) === false)
            throw new IOException("Error during opening file $filename!");
        
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
    }

    public function appendCSVData(string $filename, array $data): void {
        $path = $this->csvDir.$filename;

        if (!file_exists($path)) 
            throw new IOException("File $filename doesn't exists!");
        
        if (($handle = fopen($path, 'a')) === false)
            throw new IOException("Error during opening file $filename!");

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

}