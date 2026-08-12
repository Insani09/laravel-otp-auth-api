<script>
/**
 * Cascading wilayah:
 * - Indonesia: tabel IndoRegion lokal (province -> regency -> district).
 * - Negara lain: GeoNames melalui backend Laravel (subdivision -> city).
 *
 * Nilai yang disimpan pada hidden input selalu berupa nama tampilan;
 * value <option> hanya dipakai sebagai ID internal untuk request berikutnya.
 */
window.initRegionCascading = function (opts) {
    opts = opts || {};

    const prefix = opts.prefix || '';
    const hiddenPrefix = opts.hiddenPrefix || prefix;
    const apiBase = opts.apiBase || '/api';

    const $country = $('#' + prefix + 'country');
    const $province = $('#' + prefix + 'province');
    const $regency = $('#' + prefix + 'regency');
    const $district = $('#' + prefix + 'district');
    const $districtWrapper = $('#' + prefix + 'district-wrapper');

    const $hidCountry = $('#' + hiddenPrefix + 'reg-negara');
    const $hidProvince = $('#' + hiddenPrefix + 'reg-provinsi');
    const $hidRegency = $('#' + hiddenPrefix + 'reg-kota');
    const $hidDistrict = $('#' + hiddenPrefix + 'reg-kecamatan');

    // Fallback juga menjaga negara yang sebelumnya sudah diprioritaskan.
    const fallbackCountries = [
        { id: 'ID', text: 'Indonesia' },
        { id: 'MY', text: 'Malaysia' },
        { id: 'SG', text: 'Singapura' },
        { id: 'JP', text: 'Jepang' },
        { id: 'US', text: 'Amerika Serikat' }
    ];

    function destroySelect2($element) {
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }
    }

    function initSelect2($element, placeholder) {
        destroySelect2($element);
        $element.select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%'
        });
    }

    function resetSelect($element, placeholder) {
        $element.empty().append(new Option(placeholder, '', true, true));
        initSelect2($element, placeholder);
    }

    function fillSelect($element, placeholder, items) {
        resetSelect($element, placeholder);
        (items || []).forEach(function (item) {
            if (item && item.id && item.text) {
                $element.append(new Option(item.text, item.id, false, false));
            }
        });
        $element.trigger('change.select2');
    }

    function selectedText($element) {
        return $element.val()
            ? ($element.find('option:selected').text().trim() || '')
            : '';
    }

    function currentCountryCode() {
        return String($country.val() || '').toUpperCase();
    }

    function clearHiddenRegions() {
        $hidProvince.val('');
        $hidRegency.val('');
        $hidDistrict.val('');
    }

    function syncHidden() {
        // Selalu set, termasuk ketika pengguna mengosongkan pilihan, agar tidak ada nilai lama tersimpan.
        $hidCountry.val(selectedText($country));
        $hidProvince.val(selectedText($province));
        $hidRegency.val(selectedText($regency));
        $hidDistrict.val(selectedText($district));
    }

    function resetRegions() {
        fillSelect($province, '-- Pilih Provinsi --', []);
        fillSelect($regency, '-- Pilih Kota / Kabupaten --', []);
        fillSelect($district, '-- Pilih Kecamatan --', []);
        $province.prop('disabled', true);
        $regency.prop('disabled', true);
        $district.prop('disabled', true);
        clearHiddenRegions();
    }

    function request(url, onSuccess, onFailure) {
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            timeout: 12000,
            success: onSuccess,
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Request data wilayah gagal.';
                console.warn(message, url);
                if (onFailure) onFailure(message);
            }
        });
    }

    function loadCountries() {
        fillSelect($country, '-- Pilih Negara --', fallbackCountries);

        request(apiBase + '/geo/countries', function (response) {
            if (!response || !Array.isArray(response.results)) {
                console.warn('Format data negara tidak valid. Fallback dipakai.');
                return;
            }

            const byId = new Map();
            fallbackCountries.concat(response.results).forEach(function (item) {
                if (item && item.id && item.text) byId.set(String(item.id).toUpperCase(), item);
            });

            const countries = Array.from(byId.values()).sort(function (a, b) {
                if (a.id === 'ID') return -1;
                if (b.id === 'ID') return 1;
                return a.text.localeCompare(b.text, 'id');
            });

            const current = currentCountryCode();
            fillSelect($country, '-- Pilih Negara --', countries);
            if (current && $country.find('option[value="' + current + '"]').length) {
                $country.val(current).trigger('change.select2');
            }
        });
    }

    function loadIndonesiaProvinces() {
        fillSelect($province, '-- Sedang memuat provinsi... --', []);
        $province.prop('disabled', true);

        request(apiBase + '/provinces', function (rows) {
            const items = Array.isArray(rows) ? rows.map(function (row) {
                return { id: row.id, text: row.name };
            }) : [];
            fillSelect($province, '-- Pilih Provinsi --', items);
            $province.prop('disabled', false).trigger('change.select2');
        }, function () {
            fillSelect($province, '-- Gagal memuat provinsi --', []);
        });
    }

    function loadInternationalProvinces(countryCode) {
        fillSelect($province, '-- Sedang memuat provinsi... --', []);
        $province.prop('disabled', true);

        request(apiBase + '/geo/subdivisions/' + encodeURIComponent(countryCode), function (response) {
            const items = Array.isArray(response && response.results) ? response.results : [];
            fillSelect($province, '-- Pilih Provinsi / State --', items);
            $province.prop('disabled', false).trigger('change.select2');
        }, function () {
            fillSelect($province, '-- Gagal memuat provinsi --', []);
        });
    }

    function loadIndonesiaRegencies(provinceId) {
        fillSelect($regency, '-- Sedang memuat kota... --', []);
        $regency.prop('disabled', true);

        request(apiBase + '/regencies/' + encodeURIComponent(provinceId), function (rows) {
            const items = Array.isArray(rows) ? rows.map(function (row) {
                return { id: row.id, text: row.name };
            }) : [];
            fillSelect($regency, '-- Pilih Kota / Kabupaten --', items);
            $regency.prop('disabled', false).trigger('change.select2');
        }, function () {
            fillSelect($regency, '-- Gagal memuat kota --', []);
        });
    }

    function loadInternationalCities(countryCode, subdivisionId) {
        fillSelect($regency, '-- Sedang memuat kota... --', []);
        $regency.prop('disabled', true);

        request(
            apiBase + '/geo/cities/' + encodeURIComponent(countryCode) + '/' + encodeURIComponent(subdivisionId),
            function (response) {
                const items = Array.isArray(response && response.results) ? response.results : [];
                fillSelect($regency, '-- Pilih Kota --', items);
                $regency.prop('disabled', false).trigger('change.select2');
            },
            function () {
                fillSelect($regency, '-- Gagal memuat kota --', []);
            }
        );
    }

    function loadIndonesiaDistricts(regencyId) {
        fillSelect($district, '-- Sedang memuat kecamatan... --', []);
        $district.prop('disabled', true);

        request(apiBase + '/districts/' + encodeURIComponent(regencyId), function (rows) {
            const items = Array.isArray(rows) ? rows.map(function (row) {
                return { id: row.id, text: row.name };
            }) : [];
            fillSelect($district, items.length ? '-- Pilih Kecamatan --' : '-- Tidak ada kecamatan --', items);
            $district.prop('disabled', items.length === 0).trigger('change.select2');
        }, function () {
            fillSelect($district, '-- Gagal memuat kecamatan --', []);
        });
    }

    $country.off('change.region').on('change.region', function () {
        const countryCode = currentCountryCode();
        resetRegions();
        syncHidden();

        if (!countryCode) return;

        if (countryCode === 'ID') {
            $districtWrapper.removeClass('hidden');
            loadIndonesiaProvinces();
        } else {
            $districtWrapper.addClass('hidden');
            loadInternationalProvinces(countryCode);
        }
    });

    $province.off('change.region').on('change.region', function () {
        const provinceId = $province.val();
        fillSelect($regency, '-- Pilih Kota / Kabupaten --', []);
        fillSelect($district, '-- Pilih Kecamatan --', []);
        $regency.prop('disabled', true);
        $district.prop('disabled', true);
        $hidRegency.val('');
        $hidDistrict.val('');
        syncHidden();

        if (!provinceId) return;
        if (currentCountryCode() === 'ID') {
            loadIndonesiaRegencies(provinceId);
        } else {
            loadInternationalCities(currentCountryCode(), provinceId);
        }
    });

    $regency.off('change.region').on('change.region', function () {
        const regencyId = $regency.val();
        $hidDistrict.val('');
        syncHidden();

        if (currentCountryCode() !== 'ID') {
            $districtWrapper.addClass('hidden');
            return;
        }

        $districtWrapper.removeClass('hidden');
        fillSelect($district, '-- Pilih Kecamatan --', []);
        $district.prop('disabled', true);
        if (regencyId) loadIndonesiaDistricts(regencyId);
    });

    $district.off('change.region').on('change.region', syncHidden);

    resetRegions();
    loadCountries();

    return {
        syncHidden: syncHidden,
        setCountry: function (value) {
            if (!value) return;

            const text = String(value).trim();
            let $option = $country.find('option').filter(function () {
                return String(this.value).toLowerCase() === text.toLowerCase()
                    || $(this).text().trim().toLowerCase() === text.toLowerCase();
            }).first();

            // Kompatibel dengan data profil lama. Indonesia selalu dimapping ke ID.
            if (!$option.length) {
                const id = text.toLowerCase() === 'indonesia' ? 'ID' : text;
                $country.append(new Option(text, id, false, false));
                $option = $country.find('option').filter(function () {
                    return String(this.value) === String(id);
                }).last();
            }

            $country.val($option.val()).trigger('change.select2').trigger('change.region');
        }
    };
};
</script>
