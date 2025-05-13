<?php

namespace App\Filament\Resources\RoutingSlip\DocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->id() === $this->record->created_by),
        ];
    }
    
    public function mount(int|string $record): void
    {
        parent::mount($record);
        
        // Prevent access if user is not the creator
        if (auth()->id() !== $this->record->created_by) {
            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        // Handle file uploads
        if (isset($data['files'])) {
            // Get current files
            $currentFiles = $record->files()->pluck('file_path')->toArray();
            
            // Find files to delete
            $filesToDelete = array_diff($currentFiles, $data['files']);
            
            // Delete removed files
            foreach ($filesToDelete as $file) {
                $record->files()->where('file_path', $file)->delete();
                \Storage::disk('public')->delete($file);
            }
            
            // Add new files
            $newFiles = array_diff($data['files'], $currentFiles);
            foreach ($newFiles as $file) {
                $record->files()->create([
                    'file_path' => $file,
                    'file_name' => basename($file),
                    'mime_type' => \Storage::disk('public')->mimeType($file),
                    'file_size' => \Storage::disk('public')->size($file),
                ]);
            }
        }

        return $record;
    }
}
