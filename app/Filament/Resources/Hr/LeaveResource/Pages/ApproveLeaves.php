<?php

namespace App\Filament\Resources\Hr\LeaveResource\Pages;

use Livewire\WithFileUploads;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Filament\Pages\Actions\ButtonAction;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Hr\LeaveResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage; // Import the Storage facade

class ApproveLeaves extends Page
{
    use WithFileUploads;
    use InteractsWithRecord;

    protected static string $resource = LeaveResource::class;
    protected static string $view = 'filament.resources.hr.leave-resource.pages.approve-leaves';

    public $application_file_path;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->application_file_path = $this->record->application_file_path;
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('application_file_path')
                ->label('Upload Scanned Leave Form')
                ->directory('leave-applications')
                ->acceptedFileTypes(['image/jpeg', 'application/pdf'])
                ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                ->multiple()
                ->required()
                ->downloadable(false),
        ];
    }

    protected function getActions(): array
    {
        return [
            ButtonAction::make('save')
                ->label('Save')
                ->action('saveForm')
                ->color('primary'),
        ];
    }

    public function approveLeave()
    {
        $this->validate();

        // Ensure the directory exists before storing the file
        $directory = 'leave-applications';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
            Log::info("Directory {$directory} created successfully.");
        }


        // Check if there is an uploaded file
        $filePath = null;
        if ($this->application_file_path instanceof \Livewire\TemporaryUploadedFile) {
            // Store the file and retrieve the path
            $filePath = $this->application_file_path->storeAs($directory, $this->application_file_path->getClientOriginalName());
            Log::info("File stored at {$filePath}");
        }


        // Approve the leave and update with the file path if provided
        $this->record->update([
            'leave_status' => 'approved',
            'application_file_path' => $filePath ?: $this->record->application_file_path,
        ]);

        // Notify success
        Notification::make()
            ->title('Success')
            ->body('Leave application approved successfully!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.hr.leaves.list-approval');
    }

    public function disapproveLeave()
    {
        // Disapprove the leave
        $this->validate();
        $this->record->update([
            'leave_status' => 'disapproved',
        ]);

        // Notify danger
        Notification::make()
            ->title('Leave Disapproved')
            ->body('Leave application disapproved.')
            ->danger()
            ->send();

        return redirect()->route('filament.resources.hr.leaves.index');
    }
}
