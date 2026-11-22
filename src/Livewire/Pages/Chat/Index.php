<?php

namespace Bale\Sidulur\Livewire\Pages\Chat;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman tipis: layout dan judul milik Sidulur, ruang kerja chat milik Vada.
 * Izin `vada.access` dijaga komponen Vada saat mount.
 */
#[Layout('cms::layouts.app')]
#[Title('Chat Sidulur')]
class Index extends Component
{
    public function render()
    {
        return view('sidulur::livewire.pages.chat.index');
    }
}
