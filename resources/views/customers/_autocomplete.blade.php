{{-- 
  Customer Autocomplete Script
  Include di akhir form buat nota / edit nota
  
  Requires: 
  - Input dengan id="nama_penerima" & id="telp_penerima" & id="tujuan"
  - Input dengan id="nama_pengirim" & id="telp_pengirim"
--}}

<style>
    .autocomplete-wrapper { position: relative; }
    .autocomplete-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 1000;
      background: #fff;
      border: 1px solid #dee2e6;
      border-top: none;
      border-radius: 0 0 6px 6px;
      max-height: 240px;
      overflow-y: auto;
      box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    .autocomplete-item {
      padding: 8px 12px;
      cursor: pointer;
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.9rem;
    }
    .autocomplete-item:hover, .autocomplete-item.active {
      background: #f0f7ff;
    }
    .autocomplete-item .ac-nama { font-weight: 600; }
    .autocomplete-item .ac-meta { color: #6c757d; font-size: 0.8rem; }
    </style>
    
    <script>
    function initAutocomplete(inputId, tipe, onSelect) {
      const input = document.getElementById(inputId);
      if (!input) return;
    
      // Wrap input in relative container
      const wrapper = document.createElement('div');
      wrapper.className = 'autocomplete-wrapper';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);
    
      const dropdown = document.createElement('div');
      dropdown.className = 'autocomplete-dropdown';
      dropdown.style.display = 'none';
      wrapper.appendChild(dropdown);
    
      let debounceTimer = null;
      let activeIndex = -1;
      let currentResults = [];
    
      input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 1) { dropdown.style.display = 'none'; return; }
    
        debounceTimer = setTimeout(async () => {
          try {
            const res = await fetch(`/api/customers/search?q=${encodeURIComponent(q)}&tipe=${tipe}`);
            const data = await res.json();
            currentResults = data;
            activeIndex = -1;
            renderDropdown(data);
          } catch (e) {
            dropdown.style.display = 'none';
          }
        }, 200);
      });
    
      input.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          activeIndex = Math.min(activeIndex + 1, items.length - 1);
          items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          activeIndex = Math.max(activeIndex - 1, 0);
          items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
          e.preventDefault();
          if (currentResults[activeIndex]) selectCustomer(currentResults[activeIndex]);
        } else if (e.key === 'Escape') {
          dropdown.style.display = 'none';
        }
      });
    
      document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) dropdown.style.display = 'none';
      });
    
      function renderDropdown(results) {
        dropdown.innerHTML = '';
        if (!results.length) {
          dropdown.innerHTML = '<div class="autocomplete-item text-muted">Tidak ditemukan</div>';
          dropdown.style.display = 'block';
          return;
        }
        results.forEach((c, i) => {
          const item = document.createElement('div');
          item.className = 'autocomplete-item';
          item.innerHTML = `
            <div class="ac-nama">${escHtml(c.nama)}</div>
            <div class="ac-meta">
              ${c.no_telp ? '📞 ' + escHtml(c.no_telp) : ''}
              ${c.tujuan ? ' &bull; 📍 ' + escHtml(c.tujuan) : ''}
            </div>`;
          item.addEventListener('mousedown', (e) => {
            e.preventDefault();
            selectCustomer(c);
          });
          dropdown.appendChild(item);
        });
        dropdown.style.display = 'block';
      }
    
      function selectCustomer(c) {
        input.value = c.nama;
        dropdown.style.display = 'none';
        if (onSelect) onSelect(c);
      }
    
      function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
      // Autocomplete Penerima
      initAutocomplete('nama_penerima', 'PENERIMA', (c) => {
        const telp = document.getElementById('telp_penerima');
        const tujuan = document.getElementById('tujuan');
        if (telp && c.no_telp) telp.value = c.no_telp;
        if (tujuan && c.tujuan) tujuan.value = c.tujuan;
      });
    
      // Autocomplete Pengirim
      initAutocomplete('nama_pengirim', 'PENGIRIM', (c) => {
        const telp = document.getElementById('telp_pengirim');
        if (telp && c.no_telp) telp.value = c.no_telp;
      });
    });
    </script>