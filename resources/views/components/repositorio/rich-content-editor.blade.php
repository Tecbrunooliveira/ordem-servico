@props([
    'wireModel',
    'label',
    'placeholder' => '',
    'editorKey' => 'rich-content-editor',
    'minHeight' => 'min-h-[8rem]',
    'uploadUrl' => '',
])

<div wire:key="{{ $editorKey }}">
    <label class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <div
        wire:ignore
        x-data="richMediaEditor($wire.entangle('{{ $wireModel }}', true), @js($uploadUrl))"
        class="rich-text-editor overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
        <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2">
            <button type="button" title="Negrito" x-on:click.prevent="exec('bold')" class="rich-text-editor__btn">
                <x-icon name="bold" class="h-4 w-4" />
            </button>
            <button type="button" title="Itálico" x-on:click.prevent="exec('italic')" class="rich-text-editor__btn">
                <x-icon name="italic" class="h-4 w-4" />
            </button>
            <button type="button" title="Sublinhado" x-on:click.prevent="exec('underline')" class="rich-text-editor__btn">
                <x-icon name="underline" class="h-4 w-4" />
            </button>
            <span class="mx-1 w-px self-stretch bg-slate-200"></span>
            <button type="button" title="Lista com marcadores" x-on:click.prevent="exec('insertUnorderedList')" class="rich-text-editor__btn">
                <x-icon name="list-bullet" class="h-4 w-4" />
            </button>
            <button type="button" title="Lista numerada" x-on:click.prevent="exec('insertOrderedList')" class="rich-text-editor__btn">
                <x-icon name="numbered-list" class="h-4 w-4" />
            </button>
            <span class="mx-1 w-px self-stretch bg-slate-200"></span>
            <button
                type="button"
                title="Inserir imagem"
                x-on:click.prevent="triggerImageUpload()"
                class="rich-text-editor__btn"
                :disabled="uploading"
            >
                <x-icon name="photo" class="h-4 w-4" />
            </button>
            <button
                type="button"
                title="Enviar vídeo"
                x-on:click.prevent="triggerVideoUpload()"
                class="rich-text-editor__btn"
                :disabled="uploading"
            >
                <x-icon name="video-camera" class="h-4 w-4" />
            </button>
            <button type="button" title="Link de vídeo" x-on:click.prevent="insertVideoUrl()" class="rich-text-editor__btn">
                <x-icon name="link" class="h-4 w-4" />
            </button>
            <span class="mx-1 w-px self-stretch bg-slate-200"></span>
            <button type="button" title="Remover formatação" x-on:click.prevent="exec('removeFormat')" class="rich-text-editor__btn px-2 text-xs font-medium">
                Limpar
            </button>
            <input type="file" x-ref="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" x-on:change="onImageSelected($event)">
            <input type="file" x-ref="videoInput" accept="video/mp4,video/webm,video/quicktime" class="hidden" x-on:change="onVideoSelected($event)">
        </div>
        <div
            x-ref="editor"
            contenteditable="true"
            x-on:input="sync()"
            x-on:blur="onBlur()"
            x-on:paste="onPaste($event)"
            data-placeholder="{{ $placeholder }}"
            class="rich-text-editor__content repositorio-rich-content {{ $minHeight }} px-4 py-3 text-sm text-slate-900 outline-none"
        ></div>
        <p x-show="uploading" x-cloak class="border-t border-slate-100 bg-slate-50 px-3 py-1.5 text-xs text-slate-500">
            Enviando arquivo...
        </p>
    </div>
    @error($wireModel)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
