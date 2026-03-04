$.ajaxSetup({
  headers: {
    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
  },
})

$(document).ready(function () {
  $(".loading-option").hide()

  $(".form-block .btn-submit").click(function (e) {
    const loading = ($(this).data("loading")) ? $(this).data("loading") : "LOADING..."
    $(this).addClass("btn-loader").html(`<span class="loading"><i class="ri-refresh-line fs-16"></i></span> &nbsp;&nbsp;${loading}`).attr("disabled", true)
    $(this).closest(".form-block").submit()
  })

  $(".set-tooltip").tooltip({
    container: 'body'
  })
})

let createpassword = (type, ele) => {
  document.getElementById(type).type = document.getElementById(type).type == "password" ? "text" : "password"
  let icon = ele.childNodes[0].classList
  let stringIcon = icon.toString()

  if (stringIcon.includes("ri-eye-line")) {
    ele.childNodes[0].classList.remove("ri-eye-line")
    ele.childNodes[0].classList.add("ri-eye-off-line")
  }
  else {
    ele.childNodes[0].classList.add("ri-eye-line")
    ele.childNodes[0].classList.remove("ri-eye-off-line")
  }
}

function setNotifSuccess(message, redirect)
{
  Swal.fire({
    icon: "success",
    title: label_success,
    text: message,
  }).then((result) => {
    if (result.isConfirmed) {
      if (redirect === false)
        Swal.close()
      else if (redirect == "reload")
        location.reload()
      else
        window.location = redirect
    } else {
      Swal.close()
    }
  })
}

function setNotifFail(message)
{
  Swal.fire(label_failed, message, "error")
}

function setNotifInfo(message, redirect) {
  Swal.fire({
    icon: "info",
    title: label_info,
    text: message,
  }).then((result) => {
    if (result.isConfirmed) {
      if (redirect === undefined)
        Swal.close()
      else if (redirect == "reload")
        location.reload()
      else
        window.location = redirect
    } else {
      Swal.close()
    }
  })
}

function ajaxError(status) {
  Swal.fire(label_failed, "Maaf telah terjadi kesalahan, harap laporkan error ini", "error")
}

function ajaxLaravelError(xhr) {
  if (xhr.status == 422) {
    for (index in xhr.responseJSON.errors)

      setNotifInfo(xhr.responseJSON.errors[index][0])
  } else
    ajaxError(xhr.status)
}

function deleteConfirm(url, redirect, grid) {
  Swal.fire({
    icon: "warning",
    title: label_confirmation,
    text: string_confirm_delete,
    showCancelButton: true,
    confirmButtonText: label_yes,
    cancelButtonText: label_cancel,
    showLoaderOnConfirm: true,
    preConfirm: () => {
      $.ajax({
        type: "DELETE",
        url: url,
        dataType: "json",
        success: function (response) {
          if (response.status) {
            if (grid != undefined) {
              window.LaravelDataTables[grid].ajax.reload()
            }

            setNotifSuccess(response.message, redirect)
          } else
            setNotifInfo(response.message)
        },
        error: function (xhr, ajaxOptions, thrownError) {
          ajaxError(xhr.status)
        }
      })

      return true
    }
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.close()
    }
  })
}

function htmlEntities(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function phoneFormat(phone) {
  const len = phone.length
  const phone1 = phone.substring(0, 4);
  const phone2 = phone.substring(4, 8);
  const phone3 = phone.substring(8, 12);
  const phone4 = (len > 12) ? '-' + phone.substring(12, len) : '';

  return phone1 + '-' + phone2 + '-' + phone3 + phone4;
}

function moneyFormat(a) {
  return a.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
}

function monthFormat(month, format) {
  var format = (format == undefined) ? "mmmm" : format

  if (format == 'mmmm') {
    var fm = month_mmmm;
  } else if (format == 'mmm') {
    var fm = month_mmm;
  } else if (format == 'romawi') {
    var fm = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
  }

  return fm[month];
}

function dayFormat(day, format) {
  var index = day - 1
  var format = (format == undefined) ? "dddd" : format

  if (format == 'dddd') {
    var fd = day_dddd;
  } else if (format == 'ddd') {
    var fd = day_ddd;
  }

  return fd[index];
}

function dateFormat(date, format) {
  var format = (format == undefined) ? "{dd} {mmmm} {yyyy}" : format
  var date = new Date(date)
  var dd = date.getDate()
  var dddd = dayFormat(date.getDay())
  var mmmm = monthFormat(date.getMonth())
  var mmm = monthFormat(date.getMonth(), "mmm")
  var mm = date.getMonth() + 1
  var yyyy = date.getFullYear()
  var yyy = yyyy.toString().substring(2, 4)
  var hh = date.getHours()
  var ii = date.getMinutes()

  dd = (dd < 10) ? "0" + dd : dd
  mm = (mm < 10) ? "0" + mm : mm
  hh = (hh < 10) ? "0" + hh : hh
  ii = (ii < 10) ? "0" + ii : ii

  var result = format.replace("{dd}", dd)
    .replace("{dddd}", dddd)
    .replace("{mmmm}", mmmm)
    .replace("{mmm}", mmm)
    .replace("{mm}", mm)
    .replace("{yyyy}", yyyy)
    .replace("{yyy}", yyy)
    .replace("{hh}", hh)
    .replace("{ii}", ii)

  return result
}

function dateDiffInDay(start, end)
{
  const startdate = new Date(start)
  const enddate = new Date(end)

  return Math.round((enddate - startdate) / (1000 * 60 * 60 * 24)) + 1
}

function setSelect2(allowClear) {
  var allowClear = (allowClear == undefined) ? false : allowClear

  $(".set-select2").select2({
    placeholder: label_choose,
    allowClear: allowClear,
    width: "100%"
  })
}

function setDatePicker()
{
  $(".date-picker").daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoUpdateInput: false,
    locale: {
      format: "DD-MM-YYYY"
    }
  }).on("apply.daterangepicker", function (ev, picker) {
    $(this).val(picker.startDate.format('DD-MM-YYYY'))
  })
}

function setDateRangePicker(startInput, endInput)
{
  $(".date-range-picker").daterangepicker({
    showDropdowns: true,
    autoUpdateInput: false,
    locale: {
      format: "DD MMM YYYY"
    }
  }).on("apply.daterangepicker", function (ev, picker) {
    const start = dateFormat(picker.startDate.format('YYYY-MM-DD'), "{dd} {mmm} {yyyy}")
    const end = dateFormat(picker.endDate.format('YYYY-MM-DD'), "{dd} {mmm} {yyyy}")

    $(startInput).val(picker.startDate.format('YYYY-MM-DD'))
    $(endInput).val(picker.endDate.format('YYYY-MM-DD'))
    $(this).val(`${start} - ${end}`)
  })
}

function setTimePicker()
{
  $(".time-picker").clockpicker({
    autoclose: true
  })
}

function deleteAbsensi(url) {
  Swal.fire({
      title: 'Apakah Anda yakin?',
      text: "Data akan dihapus permanen!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, hapus!'
  }).then((result) => {
      if (result.isConfirmed) {
          $.ajax({
              url: url,
              type: 'POST',
              data: {
                  _method: 'DELETE',
                  _token: $('meta[name="csrf-token"]').attr('content')
              },
              success: function(response) {
                  Swal.fire('Berhasil!', 'Data telah dihapus.', 'success');
                  $('#table-halaqah').DataTable().ajax.reload();
              },
              error: function(xhr) {
                  Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus.', 'error');
              }
          });
      }
  });
}
