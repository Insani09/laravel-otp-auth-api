<script>
/**
 * Cascading wilayah.
 * Indonesia memakai database lokal; negara lain memakai GeoNames melalui backend.
 * Value option adalah ID/kode internal, sedangkan hidden input menyimpan label tampilan.
 */
window.initRegionCascading = function (options) {
    const opts = options || {};
    const prefix = opts.prefix || '';
    const hiddenPrefix = opts.hiddenPrefix || prefix;
    const apiBase = opts.apiBase || '/api';
    const onRegionError = typeof opts.onError === 'function'
        ? opts.onError
        : function (message) { console.warn('[Region]', message); };

    const $country = $('#' + prefix + 'country');
    const $province = $('#' + prefix + 'province');
    const $regency = $('#' + prefix + 'regency');
    const $district = $('#' + prefix + 'district');
    const $districtWrapper = $('#' + prefix + 'district-wrapper');

    const $hidCountry = $('#' + hiddenPrefix + 'reg-negara');
    const $hidProvince = $('#' + hiddenPrefix + 'reg-provinsi');
    const $hidRegency = $('#' + hiddenPrefix + 'reg-kota');
    const $hidDistrict = $('#' + hiddenPrefix + 'reg-kecamatan');

    const fallbackCountries = [
        { id: 'ID', text: 'Indonesia' },
        { id: 'MY', text: 'Malaysia' },
        { id: 'SG', text: 'Singapura' },
        { id: 'JP', text: 'Jepang' },
        { id: 'US', text: 'Amerika Serikat' }
    ];

    const countryAliases = {
        'south korea': 'KR',
        'republic of korea': 'KR',
        'korea, republic of': 'KR',
        'korea selatan': 'KR',
        'indonesia': 'ID',
        'malaysia': 'MY',
        'singapura': 'SG',
        'singapore': 'SG',
        'jepang': 'JP',
        'japan': 'JP',
        'amerika serikat': 'US',
        'united states': 'US',
        'united states of america': 'US'
    };

    let pendingCountryValue = '';

    function reportError(message, details) {
        console.error('[Region]', details || message);
        onRegionError(message || 'Gagal memuat data wilayah.');
    }

    function normalizeCountryCode(value) {
        const raw = String(value || '').trim();
        const upper = raw.toUpperCase();
        if (/^[A-Z]{2}$/.test(upper)) return upper;
        return countryAliases[raw.toLowerCase()] || '';
    }

    function currentCountryCode() {
        return normalizeCountryCode($country.val());
    }

    function destroySelect2($select) {
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
    }

    function initSelect2($select, placeholder) {
        destroySelect2($select);
        $select.select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%'
        });
    }

    function fillSelect($select, placeholder, items) {
        $select.empty().append(new Option(placeholder, '', true, true));
        (items || []).forEach(function (item) {
            if (item && item.id !== undefined && item.id !== null && item.text) {
                $select.append(new Option(item.text, item.id, false, false));
            }
        });
        initSelect2($select, placeholder);
        $select.trigger('change.select2');
    }

    function selectedText($select) {
        return $select.val()
            ? ($select.find('option:selected').text().trim() || '')
            : '';
    }

    function syncHidden() {
        $hidCountry.val(selectedText($country));
        $hidProvince.val(selectedText($province));
        $hidRegency.val(selectedText($regency));
        $hidDistrict.val(selectedText($district));
    }

    function clearRegions() {
        fillSelect($province, '-- Pilih Provinsi --', []);
        fillSelect($regency, '-- Pilih Kota / Kabupaten --', []);
        fillSelect($district, '-- Pilih Kecamatan --', []);
        $province.prop('disabled', true);
        $regency.prop('disabled', true);
        $district.prop('disabled', true);
        $districtWrapper.addClass('hidden');
        $hidProvince.val('');
        $hidRegency.val('');
        $hidDistrict.val('');
    }

    function errorMessage(xhr, textStatus, errorThrown) {
        const response = xhr && xhr.responseJSON ? xhr.responseJSON : {};

        if (response.message) return response.message;
        if (response.errors && typeof response.errors === 'object') {
            const messages = Object.values(response.errors).flat().filter(Boolean);
            if (messages.length) return messages.join(' ');
        }
        if (textStatus === 'timeout') return 'Request data wilayah terlalu lama.';
        if (!xhr || xhr.status === 0) return 'Server tidak dapat dihubungi. Periksa koneksi atau server Laravel.';
        if (xhr.status === 401) return 'Sesi login telah berakhir. Silakan login kembali.';
        if (xhr.status === 403) return 'Anda tidak memiliki izin mengambil data wilayah.';
        if (xhr.status === 404) return 'Endpoint data wilayah tidak ditemukan.';
        if (xhr.status === 422) return 'Parameter wilayah tidak valid. Pastikan kode negara memakai ISO alpha-2.';
        if (xhr.status === 429) return 'Terlalu banyak request. Silakan tunggu beberapa saat.';
        if (xhr.status >= 500) return 'Terjadi kesalahan pada server. Silakan coba lagi.';
        return errorThrown || 'Gagal memuat data wilayah.';
    }

    function request(url, onSuccess, onFailure, onComplete) {
        let requestUrl;

        try {
            requestUrl = new URL(url, window.location.origin).toString();
        } catch (error) {
            const message = 'URL endpoint wilayah tidak valid.';
            reportError(message, { type: 'url', url: url, error: error });
            if (typeof onFailure === 'function') onFailure(message);
            if (typeof onComplete === 'function') onComplete();
            return null;
        }

        return $.ajax({
            url: requestUrl,
            method: 'GET',
            dataType: 'json',
            timeout: 12000,
            headers: { Accept: 'application/json' }
        })
            .done(function (response) {
                try {
                    if (response === null || response === undefined) {
                        throw new Error('Response kosong.');
                    }
                    if (typeof onSuccess === 'function') onSuccess(response);
                } catch (error) {
                    const message = 'Data wilayah diterima tetapi gagal diproses.';
                    reportError(message, { type: 'response', url: requestUrl, error: error });
                    if (typeof onFailure === 'function') onFailure(message);
                }
            })
            .fail(function (xhr, textStatus, errorThrown) {
                const message = errorMessage(xhr, textStatus, errorThrown);
                reportError(message, {
                    type: 'http',
                    url: requestUrl,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON || xhr.responseText
                });
                if (typeof onFailure === 'function') onFailure(message, xhr);
            })
            .always(function () {
                if (typeof onComplete === 'function') onComplete();
            });
    }

    function loadCountries() {
        fillSelect($country, '-- Pilih Negara --', fallbackCountries);

        request(apiBase + '/geo/countries', function (response) {
            if (!response || !Array.isArray(response.results)) {
                throw new Error('Format response negara tidak valid.');
            }

            const byId = new Map();
            fallbackCountries.concat(response.results).forEach(function (item) {
                if (item && item.id && item.text) {
                    byId.set(String(item.id).toUpperCase(), item);
                }
            });

            const countries = Array.from(byId.values()).sort(function (a, b) {
                if (a.id === 'ID') return -1;
                if (b.id === 'ID') return 1;
                return a.text.localeCompare(b.text, 'id');
            });

            const currentCode = normalizeCountryCode(pendingCountryValue || $country.val());
            fillSelect($country, '-- Pilih Negara --', countries);

            if (currentCode && $country.find('option[value="' + currentCode + '"]').length) {
                $country.val(currentCode).trigger('change.select2').trigger('change.region');
            }
        }, function () {
            // Fallback sudah tampil; user tetap dapat memilih negara prioritas.
        });
    }

    function loadIndonesiaProvinces() {
        fillSelect($province, '-- Sedang memuat provinsi... --', []);
        $province.prop('disabled', true);

        request(apiBase + '/provinces', function (rows) {
            const items = Array.isArray(rows) ? rows.map(function (row) {
                return { id: row.id, text: row.name };
            }) : [];

            if (!items.length) throw new Error('Daftar provinsi Indonesia kosong.');
            fillSelect($province, '-- Pilih Provinsi --', items);
            $province.prop('disabled', false);
        }, function (message) {
            fillSelect($province, '-- Gagal memuat provinsi --', []);
            $province.prop('disabled', true);
        });
    }

    function loadInternationalProvinces(countryCode) {
        const code = normalizeCountryCode(countryCode);
        if (!code) {
            const message = 'Kode negara tidak valid. Gunakan ISO alpha-2.';
            fillSelect($province, '-- Negara tidak valid --', []);
            $province.prop('disabled', true);
            onRegionError(message);
            return;
        }

        fillSelect($province, '-- Sedang memuat provinsi / state... --', []);
        $province.prop('disabled', true);

        request(apiBase + '/geo/subdivisions/' + encodeURIComponent(code), function (response) {
            const items = Array.isArray(response && response.results) ? response.results : [];
            fillSelect($province, '-- Pilih Provinsi / State --', items);
            $province.prop('disabled', items.length === 0);
        }, function (message) {
            fillSelect($province, '-- Gagal memuat provinsi --', []);
            $province.prop('disabled', true);
        });
    }

    function loadIndonesiaRegencies(provinceId) {
        fillSelect($regency, '-- Sedang memuat kota... --', []);
        $regency.prop('disabled', true);

        request(apiBase + '/regencies/' + encodeURIComponent(provinceId), function (rows) {
            const items = Array.isArray(rows) ? rows.map(function (row) {
                return { id: row.id, text: row.name };
            }) : [];

            if (!items.length) throw new Error('Daftar kota/kabupaten kosong.');
            fillSelect($regency, '-- Pilih Kota / Kabupaten --', items);
            $regency.prop('disabled', false);
        }, function (message) {
            fillSelect($regency, '-- Gagal memuat kota --', []);
            $regency.prop('disabled', true);
        });
    }

    function loadInternationalCities(countryCode, subdivisionId) {
        const code = normalizeCountryCode(countryCode);
        if (!code || !subdivisionId) return;

        fillSelect($regency, '-- Sedang memuat kota... --', []);
        $regency.prop('disabled', true);

        request(
            apiBase + '/geo/cities/' + encodeURIComponent(code) + '/' + encodeURIComponent(subdivisionId),
            function (response) {
                const items = Array.isArray(response && response.results) ? response.results : [];
                fillSelect($regency, '-- Pilih Kota --', items);
                $regency.prop('disabled', items.length === 0);
            },
            function (message) {
                fillSelect($regency, '-- Gagal memuat kota --', []);
                $regency.prop('disabled', true);
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
            $district.prop('disabled', items.length === 0);
        }, function (message) {
            fillSelect($district, '-- Gagal memuat kecamatan --', []);
            $district.prop('disabled', true);
        });
    }

    $country.off('change.region').on('change.region', function () {
        const countryCode = currentCountryCode();
        clearRegions();
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
        if (regencyId) loadIndonesiaDistricts(regencyId);
    });

    $district.off('change.region').on('change.region', syncHidden);

    clearRegions();
    loadCountries();

    return {
        syncHidden: syncHidden,
        setCountry: function (value) {
            if (!value) return;

            const text = String(value).trim();
            const code = normalizeCountryCode(text);
            pendingCountryValue = text;

            let $option = $country.find('option').filter(function () {
                return (code && String(this.value).toUpperCase() === code)
                    || $(this).text().trim().toLowerCase() === text.toLowerCase();
            }).first();

            if (!$option.length) {
                const optionValue = code || text;
                $country.append(new Option(text, optionValue, false, false));
                $option = $country.find('option').filter(function () {
                    return String(this.value).toUpperCase() === String(optionValue).toUpperCase();
                }).last();
            }

            $country.val($option.val()).trigger('change.select2').trigger('change.region');
        }
    };
};
</script>
