<script setup>
definePage({
  meta: {
    layout: "blank",
    action: "read",
    subject: "Web",
  },
});
const btnLoading = ref(false);
const sekolah_id = ref();
const sekolah = ref();
const items = ref([]);
const error = ref(false);
const loadingBody = ref(true);
const isUrlErapor = ref(true);
const urlErapor = ref();
const clickMe = async () => {
  btnLoading.value = true;
  const find = items.value.find((s) => {
    return s.sekolah_id === sekolah_id.value;
  });
  await $api("/sekolah", {
    method: "POST",
    body: {
      sekolah_id: sekolah_id.value,
      pengguna_id: find?.pengguna?.pengguna_id,
    },
    async onResponse({ response }) {
      let getData = response._data;
      await fetchData();
    },
  });
};
onMounted(async () => {
  await fetchData();
});
const isDisabled = ref(false);
const jumlah = ref(0);
const table_sync = ref([]);
const semesterId = ref();
const tahunAjaranId = ref();
const fetchData = async () => {
  loadingBody.value = true;
  isDisabled.value = true;
  try {
    const response = await useApi(createUrl("/sekolah"));
    let getData = response.data.value;
    if (getData) {
      items.value = getData.sekolah;
      sekolah.value = getData.user?.sekolah;
      error.value = getData.error;
      btnLoading.value = false;
      jumlah.value = getData.jumlah;
      table_sync.value = getData.table_sync;
      isUrlErapor.value = getData.user?.erapor?.url_erapor ? false : true;
      urlErapor.value = getData.user?.erapor?.url_erapor;
      semesterId.value = getData.user?.semester?.semester_id;
      tahunAjaranId.value = getData.user?.semester?.tahun_ajaran_id;
    }
  } catch (error) {
    console.error(error);
  } finally {
    isDisabled.value = false;
    loadingBody.value = false;
  }
};
const isDialogVisible = ref(false);
const reset = async () => {
  btnLoading.value = true;
  isDialogVisible.value = false;
  await useApi(createUrl("/reset"));
  btnLoading.value = false;
  appLogout();
};
const connectToDapo = async () => {
  console.log("connectToDapo");
  await useNonApi(createUrl("normalkan"));
  await fetchData();
};
const notif = ref({
  icon: null,
  title: null,
  text: null,
  color: null,
});
const sendMessage = ref(null);
const isAlertVisible = ref(false);
const kirimData = async (data, aksi, count, next) => {
  if (data) {
    btnLoading.value = true;
    sendMessage.value = `Mengirim data ${data} (${count})`;
    await $api("/kirim-data", {
      method: "POST",
      body: {
        sekolah_id: sekolah.value?.sekolah_id,
        semester_id: semesterId.value,
        tahun_ajaran_id: tahunAjaranId.value,
        url_erapor: urlErapor.value,
        aksi: aksi,
        count: count,
      },
      async onResponse({ response }) {
        let getData = response._data;
        console.log(next);
        if (next) {
          if (getData.next) {
            let nextData = table_sync.value.find((s) => {
              return s.aksi === getData.next;
            });
            console.log(nextData);
            if (nextData) {
              kirimData(nextData.data, nextData.aksi, nextData.count, getData.next);
            }
          } else {
            notif.value = getData.notif;
            isAlertVisible.value = true;
            btnLoading.value = false;
            sendMessage.value = null;
          }
        } else {
          notif.value = getData;
          isAlertVisible.value = true;
          btnLoading.value = false;
          sendMessage.value = null;
        }
      },
    });
  } else {
    let ptk = table_sync.value.find((s) => {
      return s.aksi === "ptk";
    });
    kirimData(ptk.data, ptk.aksi, ptk.count, true);
  }
};
const url_erapor = ref("http://localhost:8154");
const refVForm = ref();
const simpanUrl = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) storeUrl();
  });
};
const storeUrl = async () => {
  await $api("/kirim-data", {
    method: "POST",
    body: {
      sekolah_id: sekolah.value?.sekolah_id,
      url_erapor: url_erapor.value,
      data: "url",
    },
    async onResponse({ response }) {
      let getData = response._data;
      notif.value = getData;
      isAlertVisible.value = true;
      await fetchData();
    },
  });
};
</script>
<template>
  <VContainer>
    <VCard color="#007BB6">
      <VCardItem>
        <template #prepend>
          <VIcon size="1.9rem" color="white" icon="tabler-database" />
        </template>
        <VCardTitle class="text-white"> e-Rapor SMK Synchronizer </VCardTitle>
      </VCardItem>
      <VCardText>
        <p class="clamp-text text-white mb-0">
          Tools untuk mengirim data Dapodik Lokal ke Aplikasi e-Rapor SMK Versi 8!
        </p>
      </VCardText>
    </VCard>
    <VCard class="mt-4" v-if="error">
      <VCardTitle> Aplikasi tidak terhubung ke Dapodik! </VCardTitle>
      <VCardItem>
        <VBtn block size="large" @click="connectToDapo"
          >Hubungkan Aplikasi ke Dapodik</VBtn
        >
      </VCardItem>
    </VCard>
    <div class="mt-4" v-else>
      <VRow class="match-height" v-if="loadingBody">
        <VCol cols="6" xl="8" md="8" sm="6">
          <VCard>
            <VCardText class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" />
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="6" xl="4" md="4" sm="6">
          <VCard>
            <VCardText class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
      <template v-if="sekolah">
        <VRow>
          <VCol cols="12">
            <VCard>
              <VForm ref="refVForm" @submit.prevent="simpanUrl">
                <VCardText>
                  <AppTextField
                    label="URL e-Rapor SMK v8. Contoh: http://localhost:8154 atau https://erapor.sekolahku.sch.id (Tanpa garis miring di akhir)"
                    placeholder="URL e-Rapor SMK v8"
                    v-model="url_erapor"
                    :loading="btnLoading"
                    :disabled="btnLoading"
                    :rules="[requiredValidator, urlValidator]"
                  >
                    <template #append>
                      <VBtn
                        :icon="$vuetify.display.smAndDown"
                        type="submit"
                        :loading="btnLoading"
                        :disabled="btnLoading"
                      >
                        <VIcon icon="tabler-device-floppy" color="#fff" size="22" />
                        <span v-if="$vuetify.display.mdAndUp" class="ms-3">Simpan</span>
                      </VBtn>
                    </template>
                  </AppTextField>
                </VCardText>
              </VForm>
            </VCard>
          </VCol>
        </VRow>
        <VRow class="match-height">
          <VCol cols="6" xl="8" md="8" sm="6">
            <VCard>
              <VTable class="text-no-wrap">
                <tbody>
                  <tr>
                    <td rowspan="3" width="10%" style="border: none">
                      <img
                        src="/images/logo-erapor.png"
                        alt="Logo e-Rapor SMK"
                        style="max-width: 100px"
                      />
                    </td>
                    <td width="30%" style="border: none">&nbsp;&nbsp;&nbsp;NPSN</td>
                    <td width="60%" style="border: none">{{ sekolah.npsn }}</td>
                  </tr>
                  <tr>
                    <td style="border: none">Nama Sekolah</td>
                    <td style="border: none">{{ sekolah.nama }}</td>
                  </tr>
                  <tr>
                    <td style="border: none">Alamat</td>
                    <td style="border: none">{{ sekolah.alamat_jalan }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VCard>
          </VCol>
          <VCol cols="6" xl="4" md="4" sm="6">
            <VCard>
              <VCardText class="text-center" style="vertical-align: middle">
                <VBtn
                  :loading="btnLoading"
                  :disabled="btnLoading || isUrlErapor"
                  size="large"
                  @click="kirimData(null, null, null, false)"
                >
                  <font-awesome-icon
                    icon="fa-solid fa-cloud-arrow-up"
                  />&nbsp;&nbsp;&nbsp;<strong>KIRIM DATA</strong>
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
        <VRow v-if="!loadingBody">
          <VCol cols="12">
            <VAlert color="error" class="text-center" v-if="sendMessage">
              <h3 class="text-white">{{ sendMessage }}</h3>
            </VAlert>
            <VAlert color="secondary" class="text-center" v-else>
              <h3 class="text-white">DATA YANG AKAN DIKIRIM</h3>
            </VAlert>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12">
            <VCard v-if="loadingBody">
              <VCardText class="text-center">
                <VProgressCircular :size="60" indeterminate color="error" />
              </VCardText>
            </VCard>
            <VCard v-else>
              <VTable class="text-no-wrap">
                <thead>
                  <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="65%">Data</th>
                    <th class="text-center" width="15%">Jml Data</th>
                    <th class="text-center" width="15%">Kirim Satuan</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="jumlah">
                    <tr v-for="(item, index) in table_sync" :key="index">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td>{{ item.data }}</td>
                      <td class="text-center">{{ item.count }}</td>
                      <td class="text-center">
                        <VBtn
                          :loading="btnLoading"
                          :disabled="btnLoading || isUrlErapor"
                          size="small"
                          @click="kirimData(item.data, item.aksi, item.count, false)"
                        >
                          Kirim Data
                        </VBtn>
                      </td>
                    </tr>
                  </template>
                  <template v-else>
                    <tr>
                      <td colspan="4" class="text-center">
                        Tidak ada data yang mengalami perubahan
                      </td>
                    </tr>
                  </template>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="3" class="text-right">Jumlah</th>
                    <th class="text-center">{{ jumlah }}</th>
                  </tr>
                </tfoot>
              </VTable>
            </VCard>
          </VCol>
        </VRow>
      </template>
      <template v-else>
        <VCard>
          <VCardText>
            <AppAutocomplete
              v-model="sekolah_id"
              :items="items"
              item-title="nama"
              item-value="sekolah_id"
              placeholder="Pilih Sekolah"
              :disabled="isDisabled"
              :loading="isDisabled"
            >
              <template #append>
                <VBtn
                  :icon="$vuetify.display.smAndDown"
                  @click="clickMe"
                  :loading="btnLoading"
                  :disabled="btnLoading"
                >
                  <VIcon icon="tabler-device-floppy" color="#fff" size="22" />
                  <span v-if="$vuetify.display.mdAndUp" class="ms-3">Simpan</span>
                </VBtn>
              </template>
            </AppAutocomplete>
          </VCardText>
        </VCard>
      </template>
      <ShowAlert
        :color="notif.color"
        :icon="notif.icon"
        :title="notif.title"
        :text="notif.text"
        :disable-time-out="false"
        v-model:isSnackbarVisible="isAlertVisible"
        v-if="notif.color"
      ></ShowAlert>
      <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
        <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
        <VCard title="Apakah Anda yakin?">
          <VCardText> Tindakan ini akan mengambalikan Aplikasi ke awal! </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false">
              Batal
            </VBtn>
            <VBtn @click="reset"> Yakin </VBtn>
          </VCardText>
        </VCard>
      </VDialog>
    </div>
  </VContainer>
</template>
