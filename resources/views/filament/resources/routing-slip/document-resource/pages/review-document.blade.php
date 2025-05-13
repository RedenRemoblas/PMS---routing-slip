<x-filament::page>
    <x-filament::card>
        <div class="space-y-6">
            @if($record->document_type === 'physical')
                <div class="rounded-md bg-yellow-50 p-4 border-l-4 border-yellow-400">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            @svg('heroicon-s-document', 'h-5 w-5 text-yellow-400')
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">PHYSICAL COPY</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>This document is a physical copy. The original document exists in paper form.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <form action="{{ route('routing-slip.update-document', ['id' => $record->id]) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <h3 class="text-lg font-medium">Document Information</h3>
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Tracking No.</label>
                            <p class="mt-1 font-semibold">RS-{{ $record->id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Title</label>
                            @if($record->status !== 'approved' && $record->status !== 'rejected')
                                <input type="text" name="title" value="{{ $record->title }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @else
                                <p class="mt-1">{{ $record->title }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <p class="mt-1">
                                <x-filament::badge
                                    :color="match($record->status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning',
                                    }"
                                >
                                    {{ ucfirst($record->status) }}
                                </x-filament::badge>
                            </p>
                        </div>
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-500">Remarks</label>
                            @if($record->status !== 'approved' && $record->status !== 'rejected')
                                <textarea name="remarks" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Enter document remarks here...">{{ $record->remarks }}</textarea>
                            @else
                                <p class="mt-1">{{ $record->remarks ?? 'No remarks' }}</p>
                            @endif
                        </div>
                    </div>
                    @if($record->status !== 'approved' && $record->status !== 'rejected')
                        <div class="mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Save Changes
                            </button>
                        </div>
                    @endif
                </div>
            </form>

            <div>
                <h3 class="text-lg font-medium">Approval Sequence</h3>
                <div class="mt-4 space-y-4">
                    @foreach ($record->sequences()->orderBy('sequence_number')->get() as $sequence)
                        <div class="flex items-center justify-between border-b pb-4">
                            <div>
                                <p class="font-medium">{{ $sequence->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ ucfirst($sequence->admin_type) }}</p>
                                @if ($sequence->remarks)
                                    <p class="mt-1 text-sm">{{ $sequence->remarks }}</p>
                                @endif
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-500">
                                    {{ $sequence->acted_at ? $sequence->acted_at->format('M d, Y H:i') : 'Pending' }}
                                </span>
                                <x-filament::badge
                                    :color="match($sequence->status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }"
                                >
                                    {{ ucfirst($sequence->status) }}
                                </x-filament::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($record->document_type !== 'physical')
                <div>
                    <h3 class="text-lg font-medium">Original Documents</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @php
                            $originalFiles = $record->files()->where(function($query) {
                                $query->where('file_type', 'original')->orWhereNull('file_type');
                            })->get();
                        @endphp
                        @if($originalFiles->count() > 0)
                            @foreach ($originalFiles as $file)
                                <div class="flex items-center space-x-4 rounded-lg border p-4">
                                    <div class="flex-1">
                                        <p class="font-medium truncate">{{ $file->file_name }}</p>
                                        <p class="text-sm text-gray-500">{{ number_format($file->file_size / 1024, 2) }} KB</p>
                                        <p class="text-sm text-gray-500">Uploaded by: {{ $file->uploader->name ?? 'Unknown' }}</p>
                                        <p class="text-sm text-gray-500">Date: {{ $file->created_at ? $file->created_at->format('M d, Y H:i') : 'Unknown' }}</p>
                                        <a 
                                            href="{{ route('file.download', $file->id) }}"
                                            target="_blank"
                                            class="mt-2 inline-flex items-center text-sm text-primary-600 hover:text-primary-500"
                                        >
                                            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4 mr-1')
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-span-3">
                                <p class="text-sm text-gray-500">No original documents have been uploaded yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium">Supporting Documents</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @php
                            $supportingFiles = $record->files()->where('file_type', 'supporting')->get();
                        @endphp
                        @if($supportingFiles->count() > 0)
                            @foreach ($supportingFiles as $file)
                                <div class="flex items-center space-x-4 rounded-lg border p-4">
                                    <div class="flex-1">
                                        <p class="font-medium truncate">{{ $file->file_name }}</p>
                                        <p class="text-sm text-gray-500">{{ number_format($file->file_size / 1024, 2) }} KB</p>
                                        <p class="text-sm text-gray-500">Uploaded by: {{ $file->uploader->name ?? 'Unknown' }}</p>
                                        <p class="text-sm text-gray-500">Date: {{ $file->created_at ? $file->created_at->format('M d, Y H:i') : 'Unknown' }}</p>
                                        <a 
                                            href="{{ route('file.download', $file->id) }}"
                                            target="_blank"
                                            class="mt-2 inline-flex items-center text-sm text-primary-600 hover:text-primary-500"
                                        >
                                            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4 mr-1')
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-span-3">
                                <p class="text-sm text-gray-500">No supporting documents have been uploaded yet.</p>
                            </div>
                        @endif
                    </div>
                    
                    @php
                        $currentSequence = $record->sequences()
                            ->where('user_id', auth()->id())
                            ->first();
                    @endphp
                    
                    @if($currentSequence)
                        <div class="mt-4">
                            <form action="{{ route('routing-slip.upload-file', ['id' => $record->id]) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="file-upload" class="block text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                    <div class="mt-1 flex items-center">
                                        <input id="file-upload" name="file" type="file" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-primary-50 file:text-primary-700
                                            hover:file:bg-primary-100
                                        " required>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">PDF, Word, Excel, or image files up to 5MB</p>
                                </div>
                                <div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        @svg('heroicon-o-arrow-up-tray', 'w-4 h-4 mr-1')
                                        Upload Document
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament::page>
