<div class="card">
    <div class="card-body">
        <h5>Data Absensi</h5>

        <div class="row mb-3">
            <div class="col-md-4 my-2">
                <label for="position">Pilih Jabatan:</label>
                <select id="position" class="form-control form-select">
                    <option value="">Semua Jabatan</option>
                </select>
            </div>
            <div class="col-md-4 my-2">
                <label for="month">Pilih Bulan:</label>
                <input type="month" id="month" class="form-control">
            </div>
            <div class="col-md-4 my-2 d-flex align-items-end">
                <button id="filter" class="btn btn-primary w-100 me-2">Tampilkan</button>
                <button id="export" class="btn btn-success w-100">Export Excel</button>
            </div>

        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle" id="attendance-table">
                <thead>
                    <tr id="date-header">
                        <th>Nama</th>
                    </tr>
                </thead>
                <tbody id="attendance-body"></tbody>
            </table>
        </div>

        <div id="pagination" class="d-flex justify-content-center align-items-center mt-3"></div>
    </div>
</div>


<script>
    let currentPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date();
        const monthInput = document.getElementById('month');
        monthInput.value = today.toISOString().slice(0, 7);

        loadPositions();
        loadData();

        document.getElementById('filter').addEventListener('click', () => {
            currentPage = 1;
            loadData();
        });
    });

    function loadPositions() {
        fetch(`{{ route('attendance.positions') }}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('position');
                data.forEach(pos => {
                    const opt = document.createElement('option');
                    opt.value = pos.id;
                    opt.textContent = pos.group_name;
                    select.appendChild(opt);
                });
            });
    }

    function loadData(page = 1) {
        const month = document.getElementById('month').value;
        const positionId = document.getElementById('position').value;

        const params = new URLSearchParams({
            month,
            page
        });
        if (positionId) params.append('position_id', positionId);

        fetch(`{{ route('attendance.summary.data') }}?${params}`)
            .then(res => res.json())
            .then(data => renderTable(data));
    }

    function renderTable(data) {
        const tableHead = document.getElementById('date-header');
        const tableBody = document.getElementById('attendance-body');
        const pagination = document.getElementById('pagination');

        const today = new Date().toISOString().slice(0, 10);
        tableHead.innerHTML = '<th>Nama</th>';
        tableBody.innerHTML = '';

        data.dates.forEach(date => {
            const d = new Date(date);
            const dateDisplay = `${d.getDate().toString().padStart(2, '0')}/${d.getMonth() + 1}`;
            const isToday = date === today ? 'bg-primary text-white fw-bold' : '';
            tableHead.innerHTML += `<th class="${isToday}">${dateDisplay}</th>`;
        });

        if (!data.rows || data.rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${data.dates.length + 1}">Tidak ada data</td></tr>`;
            pagination.innerHTML = '';
            return;
        } else {
            data.rows.forEach(row => {
                let tr = `<tr><td class="text-start">${row.name}</td>`;
                Object.values(row.attendance).forEach(status => {
                    let color = '';
                    if (status === 'hadir') color = '#d1e7dd';
                    else if (status === 'alpha') color = '#f8d7da';
                    else if (status === 'izin') color = '#cff4fc';
                    else if (status === 'sakit') color = '#fff3cd';
                    else color = '#fff';
                    tr +=
                        `<td style="background-color:${color} !important;color:#fff;text-transform:capitalize;">${status}</td>`;
                });
                tr += '</tr>';
                tableBody.innerHTML += tr;
            });
        }

        const last = data.pagination.last_page;
        const current = data.pagination.current_page;
        let html = `<nav><ul class="pagination mb-0">`;

        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current - 1}">«</a></li>`;

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        for (let i = start; i <= end; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        html += `<li class="page-item ${current === last ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current + 1}">»</a></li></ul></nav>`;
        pagination.innerHTML = html;

        document.querySelectorAll('#pagination a.page-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (!isNaN(page)) loadData(page);
            });
        });
    }

    document.getElementById('export').addEventListener('click', () => {
        const month = document.getElementById('month').value;
        const positionId = document.getElementById('position').value;
        let url = `{{ route('attendance.export') }}?month=${month}`;
        if (positionId) url += `&position_id=${positionId}`;
        window.location.href = url;
    });
</script>
