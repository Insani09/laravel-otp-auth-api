<style>
/* Select2 Dark Mode — disesuaikan tema Tailwind slate/blue */
.select2-container--default .select2-selection--single {
    background-color: #020617 !important;
    border: 1px solid #1e293b !important;
    border-radius: 0.75rem !important;
    height: 42px !important;
    padding: 6px 12px !important;
    color: #e2e8f0 !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #e2e8f0 !important;
    line-height: 28px !important;
    padding-left: 0 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
.select2-container--default.select2-container--disabled .select2-selection--single {
    background-color: #0f172a !important;
    cursor: not-allowed !important;
    opacity: 0.65;
}
.select2-dropdown {
    background-color: #0f172a !important;
    border: 1px solid #1e293b !important;
    border-radius: 0.75rem !important;
    overflow: hidden;
    z-index: 9999 !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #020617 !important;
    border: 1px solid #1e293b !important;
    border-radius: 0.5rem !important;
    color: #e2e8f0 !important;
    outline: none !important;
}
.select2-container--default .select2-results__option {
    color: #cbd5e1 !important;
    padding: 8px 12px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #2563eb !important;
    color: #fff !important;
}
.select2-container--default .select2-results__option--selected {
    background-color: #1e293b !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 1px #2563eb;
}
.select2-container {
    width: 100% !important;
}
</style>
