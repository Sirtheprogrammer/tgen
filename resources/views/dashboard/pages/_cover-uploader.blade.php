@php
    $existingCoverImages = isset($page) ? ($page->cover_images ?? []) : [];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-6 py-6 text-white sm:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-indigo-300">First impression</p>
                <h2 class="text-xl font-bold">Build your cover gallery</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Choose up to four images together or add them in separate selections. Visitors see this gallery before continuing to the page.</p>
            </div>
            <p class="shrink-0 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold"><span id="coverTotalCount">{{ count($existingCoverImages) }}</span><span class="text-slate-300"> / 4 images</span></p>
        </div>
    </div>

    <div class="p-6 sm:p-8">
        @if(count($existingCoverImages) > 0)
            <div class="mb-6">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <p class="text-sm font-semibold text-slate-900">Current gallery</p>
                    <p class="text-xs text-slate-500">Select an image to remove it when you save.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($existingCoverImages as $coverImage)
                        <label class="group relative block cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm">
                            <img src="{{ asset('storage/'.$coverImage) }}" alt="Cover image {{ $loop->iteration }}" class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
                            <input type="checkbox" name="remove_cover_images[]" value="{{ $coverImage }}" class="peer sr-only existing-cover-removal">
                            <span class="absolute inset-0 bg-slate-950/0 transition peer-checked:bg-red-950/65"></span>
                            <span class="absolute bottom-2 left-2 right-2 rounded-lg bg-white/90 px-2 py-2 text-center text-xs font-bold text-slate-700 shadow-sm backdrop-blur peer-checked:bg-red-500 peer-checked:text-white">Click to keep / remove</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div id="coverDropZone" role="button" tabindex="0" aria-controls="coverImages" class="group rounded-2xl border-2 border-dashed {{ $errors->has('cover_images') || $errors->has('cover_images.*') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} px-5 py-8 text-center transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100 sm:px-8">
            <input type="file" id="coverImages" name="cover_images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
            <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 transition group-hover:-translate-y-1">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </span>
            <p class="text-base font-bold text-slate-900">Drop several images here</p>
            <p class="mt-1 text-sm text-slate-600">or click to browse and select more than one</p>
            <p class="mt-3 text-xs font-medium text-slate-500">JPEG, PNG or WebP · 5 MB maximum per image</p>
        </div>

        <p id="coverSelectionError" class="mt-3 hidden text-sm font-medium text-red-600" role="alert"></p>
        <div id="coverPreview" class="mt-6 hidden">
            <div class="mb-3 flex items-center justify-between"><p class="text-sm font-semibold text-slate-900">Ready to upload</p><button type="button" id="clearCoverImages" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 focus:underline">Clear new images</button></div>
            <div id="coverPreviewGrid" class="grid grid-cols-2 gap-3 sm:grid-cols-4"></div>
        </div>

        @error('cover_images')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('cover_images.*')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</section>

<script>
function initializeCoverUploader() {
    const dropZone = document.getElementById('coverDropZone');
    const input = document.getElementById('coverImages');
    const preview = document.getElementById('coverPreview');
    const previewGrid = document.getElementById('coverPreviewGrid');
    const totalCount = document.getElementById('coverTotalCount');
    const error = document.getElementById('coverSelectionError');
    const clearButton = document.getElementById('clearCoverImages');
    const removalInputs = [...document.querySelectorAll('.existing-cover-removal')];
    let selectedFiles = [];

    if (!dropZone || !input || dropZone.dataset.initialized === 'true') {
        return;
    }

    dropZone.dataset.initialized = 'true';

    const activeExistingCount = () => removalInputs.filter((checkbox) => !checkbox.checked).length;
    const fileKey = (file) => `${file.name}-${file.size}-${file.lastModified}`;
    const syncInput = () => {
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };
    const render = () => {
        previewGrid.replaceChildren();
        totalCount.textContent = activeExistingCount() + selectedFiles.length;
        preview.classList.toggle('hidden', selectedFiles.length === 0);
        selectedFiles.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm';
            const image = document.createElement('img');
            image.className = 'aspect-square w-full object-cover';
            image.alt = `New cover image ${index + 1}`;
            image.src = URL.createObjectURL(file);
            image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
            const badge = document.createElement('span');
            badge.className = 'absolute left-2 top-2 rounded-full bg-slate-950/80 px-2 py-1 text-[11px] font-bold text-white';
            badge.textContent = `New ${index + 1}`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-lg font-bold text-slate-700 shadow hover:bg-red-500 hover:text-white';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                syncInput();
                render();
            });
            card.append(image, badge, remove);
            previewGrid.append(card);
        });
    };
    const addFiles = (files) => {
        error.classList.add('hidden');
        const images = [...files].filter((file) => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type));
        const uniqueFiles = images.filter((file) => !selectedFiles.some((selected) => fileKey(selected) === fileKey(file)));
        const availableSlots = 4 - activeExistingCount() - selectedFiles.length;
        if (uniqueFiles.length > availableSlots) {
            error.textContent = availableSlots > 0 ? `Only ${availableSlots} more image${availableSlots === 1 ? '' : 's'} can be added.` : 'The four cover slots are full. Remove an image first.';
            error.classList.remove('hidden');
        }
        selectedFiles.push(...uniqueFiles.slice(0, Math.max(availableSlots, 0)));
        syncInput();
        render();
    };

    dropZone.addEventListener('click', (event) => {
        if (event.target !== input) {
            input.click();
        }
    });
    dropZone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });
    input.addEventListener('change', () => addFiles(input.files));
    ['dragenter', 'dragover'].forEach((name) => dropZone.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
    }));
    ['dragleave', 'drop'].forEach((name) => dropZone.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
    }));
    dropZone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
    removalInputs.forEach((checkbox) => checkbox.addEventListener('change', () => {
        selectedFiles = selectedFiles.slice(0, Math.max(4 - activeExistingCount(), 0));
        syncInput();
        render();
    }));
    clearButton.addEventListener('click', () => {
        selectedFiles = [];
        syncInput();
        render();
    });
    render();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCoverUploader, { once: true });
} else {
    initializeCoverUploader();
}

document.addEventListener('livewire:navigated', initializeCoverUploader);
</script>
