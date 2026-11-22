{{--
| Panel pemohon tambahan dari Sidulur. Dirender Vada lewat ContactContext
| (`panelView` + `panelData`) pada panel kolom maupun drawer.
| Parameter: $phone, $variant.
--}}
<livewire:sidulur.shared-components.chat.service-form
    :phone="$phone"
    :key="'sidulur-chat-form-'.$variant" />
