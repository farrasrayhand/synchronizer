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
const isAlertVisible = ref(false);
const kirimData = async (data) => {
  await $api("/kirim-data", {
    method: "POST",
    body: {
      sekolah_id: sekolah_id.value,
      data: data,
    },
    async onResponse({ response }) {
      let getData = response._data;
      notif.value = getData;
      isAlertVisible.value = true;
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
                  :disabled="btnLoading"
                  size="large"
                  @click="kirimData"
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
            <VAlert color="secondary" class="text-center">
              <h3 class="text-white">DATA YANG AKAN DIKIRIM</h3>
            </VAlert>
            <VAlert color="error" class="text-center mt-4" v-if="error">
              <h3 class="text-white">{{ message }}</h3>
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
                          :disabled="btnLoading"
                          size="small"
                          @click="kirimData(item.aksi)"
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
