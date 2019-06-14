<template>
    <b-modal class="ex-add-dxf-modal"
             ref="addDxfFileModal"
             size="lg"
             hide-footer
             hide-header
             :no-close-on-backdrop="true"
             centered>
        <button class="ex-close-btn" @click="hide()"/>
        <div class="container position-relative">
            <b-row style="margin-top: 60px">
                <b-col class="col-xl-12">
                    <vue-dropzone ref="myVueDropzone" v-show="!uploadedFile.name" id="dropzone2"
                                  @vdropzone-success="uploadImgSuccess"
                                  @vdropzone-file-added="fileAdded"
                                  @vdropzone-removed-file="fileRemoved()"
                                  :options="dropzoneOptions"
                                  :class="{ exDropzoneCustom: !uploadedFile.name }">
                    </vue-dropzone>
                    <div class="ex-uploaded-file-details" v-show="uploadedFile.name">
                        <v-menu bottom left class="ex-thumbnail-actions-dropdown"
                                content-class="ex-dxf-file-dropdown-three-dots">
                            <v-btn icon slot="activator" dark>
                                <v-icon>more_vert</v-icon>
                            </v-btn>
                            <v-list>
                                <v-list-tile @click="triggerDropzone">
                                    <v-list-tile-title>{{ $tc('scenario_wizard.dxf.file_options', 1) }}
                                    </v-list-tile-title>
                                </v-list-tile>
                                <v-list-tile class="ex-dropdown-divider" @click="$refs.myVueDropzone.removeAllFiles()">
                                    <v-list-tile-title class="ex-delete">{{ $tc('scenario_wizard.dxf.file_options', 2)
                                        }}
                                    </v-list-tile-title>
                                </v-list-tile>
                            </v-list>
                        </v-menu>
                        <img :src="defaultThumbnail" alt="default-thumbnail">
                        <p class="mt-3">{{ $t('global.filename') }}: <span>{{ uploadedFile.originalName }}</span></p>
                    </div>
                </b-col>
            </b-row>
            <template v-if="uploadedFile.name">
                <b-row>
                    <div class="col-lg">
                        <ul class="ex-coincidence-check mb-4">
                            <li>
                                <input type="radio"
                                       class="ex-product-type"
                                       v-model="groundType"
                                       :value="1"
                                       id="option_aboveground" @change="changeGroundType" name="groundType">
                                <label for="option_aboveground">{{ $t('scenario_wizard.dxf.aboveground') }}</label>
                                <div class="check"/>
                            </li>
                            <li>
                                <input type="radio"
                                       class="ex-product-type"
                                       v-model="groundType"
                                       :value="2"
                                       id="option_underground" @change="changeGroundType" name="groundType">
                                <label for="option_underground">{{ $t('scenario_wizard.dxf.underground') }}</label>
                                <div class="check"/>
                            </li>
                        </ul>
                    </div>
                </b-row>
                <hr class="mb-4">
                <template v-if="groundType">
                    <b-row>
                        <div class="col">
                            <p class="ex-assign-level-title">{{ $t('scenario_wizard.dxf.assign_to_levels') }}</p>
                            <div class="ex-assign-levels position-relative"
                                 v-for="(assignedLevel, index) in assignedLevels" :key="index">
                                <i class="delete" v-if="assignedLevels.length > 1" @click="deleteAssignedLevel(index)"/>
                                <ul class="ex-coincidence-check mb-4">
                                    <li>
                                        <input type="radio"
                                               class="ex-product-type"
                                               v-model="assignedLevel.singleRangeLevel"
                                               :value="1"
                                               :id="'option_single_level' + index" :name="'singleRangeLevel' + index">
                                        <label :for="'option_single_level' + index">{{
                                            $t('scenario_wizard.dxf.single_level') }}</label>
                                        <div class="check"/>
                                    </li>
                                    <li>
                                        <input type="radio"
                                               class="ex-product-type"
                                               v-model="assignedLevel.singleRangeLevel"
                                               :value="2"
                                               :id="'option_range_level' + index" :name="'singleRangeLevel' + index">
                                        <label :for="'option_range_level' + index">{{
                                            $t('scenario_wizard.dxf.range_level') }}</label>
                                        <div class="check"/>
                                    </li>
                                </ul>
                                <template v-if="assignedLevel.singleRangeLevel === 1">
                                    <p class="ex-choose-level">{{ $t('scenario_wizard.dxf.choose_level') }}</p>
                                    <div class="ex-unit-select mb-4">
                                        <v-select :items="filteredLevelItems(levelItems[groundType - 1], index)"
                                                  v-model="assignedLevel.levelItem"
                                                  :auto="true"
                                                  single-line
                                                  :hide-details="true"
                                        >
                                        </v-select>
                                    </div>
                                </template>
                                <div v-if="assignedLevel.singleRangeLevel === 2">
                                    <div style="display: inline-block" class="mr-3">
                                        <p class="ex-choose-level">{{ $t('global.from') }}</p>
                                        <div class="ex-unit-select mb-4">
                                            <v-select
                                                    :items="rangeFilteredLevelItems(levelItems[groundType - 1], index)"
                                                    v-model="assignedLevel.levelItemFrom"
                                                    :auto="true"
                                                    single-line
                                                    :hide-details="true"
                                                    @change="(value) => levelItemFromChanged(assignedLevel, value)"
                                            >
                                            </v-select>
                                        </div>
                                    </div>
                                    <div style="display: inline-block">
                                        <p class="ex-choose-level">{{ $t('global.to') }}</p>
                                        <div class="ex-unit-select mb-4">
                                            <v-select :items="getToLevelItems(assignedLevel.levelItemFrom)"
                                                      v-model="assignedLevel.levelItemTo"
                                                      :auto="true"
                                                      single-line
                                                      :hide-details="true"
                                            >
                                            </v-select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="ex-cancel-btn mb-4" @click="addLevels">
                                + {{ $t('scenario_wizard.dxf.assign_to_levels_button') }}
                            </button>
                        </div>
                    </b-row>
                    <hr class="mb-4">
                </template>
                <template v-if="checkSpecificLevel()">
                    <b-row>
                        <div class="col">
                            <p class="ex-assign-level-title">
                                {{ $t('scenario_wizard.dxf.specify_levels_title', {level: (groundType == 1 ? 1 : -1)})
                                }}
                            </p>
                            <div class="ex-specify-levels mb-5">
                                <ul class="ex-coincidence-check mb-4">
                                    <li class="mb-3">
                                        <input type="radio"
                                               class="ex-product-type"
                                               v-model="specifyLevelUnderAbove"
                                               :value="1"
                                               id="option_spec_under1" name="specifyLevelUnderAbove">
                                        <label for="option_spec_under1">
                                            {{ groundType == 1 ? $t('scenario_wizard.dxf.completely_aboveground') :
                                            $t('scenario_wizard.dxf.completely_underground') }}
                                        </label>
                                        <div class="check"/>
                                    </li>
                                    <li>
                                        <input type="radio"
                                               class="ex-product-type"
                                               v-model="specifyLevelUnderAbove"
                                               :value="2"
                                               id="option_spec_above1" name="specifyLevelUnderAbove">
                                        <label for="option_spec_above1">
                                            {{ groundType == 1 ? $t('scenario_wizard.dxf.partly_aboveground') :
                                            $t('scenario_wizard.dxf.partly_underground') }}
                                        </label>
                                        <div class="check"/>
                                    </li>
                                </ul>
                                <template v-if="specifyLevelUnderAbove === 2">
                                    <p>{{ $t('scenario_wizard.dxf.specify_levels_text') }}</p>
                                    <input type="number" class="ex-partly-underground" v-model="partlyUndergroundNeg">
                                    <div class="ex-unit-select">
                                        <v-select :items="unitItems"
                                                  v-model="partlyAboveUndergroundUnit"
                                                  :auto="true"
                                                  single-line
                                                  :hide-details="true"
                                        >
                                        </v-select>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </b-row>
                    <hr class="mb-4 mt-0">
                </template>
                <b-row>
                    <div class="col">
                        <p class="ex-assign-level-title">{{ $t('scenario_wizard.dxf.specify_layer_names', {level: 1})
                            }}</p>
                        <div class="ex-specify-layer-names">
                            <p>{{ $t('scenario_wizard.dxf.wall_layer_names') }}</p>
                            <div class="ex-unit-select mb-4">
                                <v-select :items="wallLayerNames"
                                          content-class="ex-dropdown-specify-layer-names"
                                          v-model="wallLayerName"
                                          :auto="true"
                                          single-line
                                          multiple
                                          chips
                                          :deletable-chips="true"
                                          @input="onInputSpecifyLayerNames"
                                          placeholder="Select"
                                          :hide-details="true"
                                >
                                </v-select>
                            </div>
                            <p>{{ $t('scenario_wizard.dxf.window_layer_names') }}</p>
                            <div class="ex-unit-select mb-4">
                                <v-select :items="windowLayerNames"
                                          content-class="ex-dropdown-specify-layer-names"
                                          v-model="windowLayerName"
                                          :auto="true"
                                          single-line
                                          multiple
                                          chips
                                          :deletable-chips="true"
                                          @input="onInputSpecifyLayerNames"
                                          placeholder="Select"
                                          :hide-details="true"
                                >
                                </v-select>
                            </div>
                            <p>{{ $t('scenario_wizard.dxf.door_layer_names') }}</p>
                            <div class="ex-unit-select mb-4">
                                <v-select :items="doorLayerNames"
                                          content-class="ex-dropdown-specify-layer-names"
                                          v-model="doorLayerName"
                                          :auto="true"
                                          single-line
                                          multiple
                                          chips
                                          :deletable-chips="true"
                                          @input="onInputSpecifyLayerNames"
                                          placeholder="Select"
                                          :hide-details="true"
                                >
                                </v-select>
                            </div>
                            <hr class="mt-4 mb-4">
                            <p>{{ $t('scenario_wizard.dxf.not_assigned_layer_names') }}</p>
                            <ul class="not-assigned-list">
                                <li v-for="(item, index) in filteredNames()" :key="index">
                                    <span class="mr-3">-</span>{{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </b-row>
                <hr class="mb-4 mt-0">
                <b-row>
                    <div class="col">
                        <p class="ex-assign-level-title">{{ $t('scenario_wizard.dxf.dimension_details') }}</p>
                        <h5 class="ex-step-used-subtitle mb-3">{{ $t('scenario_wizard.dxf.dimension_details_sub')
                            }}</h5>
                        <div class="row mb-2">
                            <div class="col-lg-3">
                                <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.ceiling_height')
                                    }}</label>
                                <input type="number" min="0" class="ex-input-floor-roof"
                                       v-model="dimensionDetails.ceilingHeight.value">
                                <div class="ex-unit-select">
                                    <v-select :items="unitItems"
                                              v-model="dimensionDetails.ceilingHeight.unit"
                                              :auto="true"
                                              single-line
                                              :hide-details="true"
                                    >
                                    </v-select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.door_height') }}</label>
                                <input type="number" min="0" class="ex-input-floor-roof"
                                       v-model="dimensionDetails.doorHeight.value">
                                <div class="ex-unit-select">
                                    <v-select :items="unitItems"
                                              v-model="dimensionDetails.doorHeight.unit"
                                              :auto="true"
                                              single-line
                                              :hide-details="true"
                                    >
                                    </v-select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.window_height')
                                    }}</label>
                                <input type="number" min="0" class="ex-input-floor-roof"
                                       v-model="dimensionDetails.windowHeight.value">
                                <div class="ex-unit-select">
                                    <v-select :items="unitItems"
                                              v-model="dimensionDetails.windowHeight.unit"
                                              :auto="true"
                                              single-line
                                              :hide-details="true"
                                    >
                                    </v-select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-lg-3">
                                <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.window_sill_height')
                                    }}</label>
                                <input type="number" min="0" class="ex-input-floor-roof"
                                       v-model="dimensionDetails.windowSillHeight.value">
                                <div class="ex-unit-select">
                                    <v-select :items="unitItems"
                                              v-model="dimensionDetails.windowSillHeight.unit"
                                              :auto="true"
                                              single-line
                                              :hide-details="true"
                                    >
                                    </v-select>
                                </div>
                            </div>
                        </div>
                    </div>
                </b-row>
                <hr class="mb-4 mt-0">
            </template>
            <b-row>
                <div class="col text-right">
                    <button type="button" @click="addDxfFile" class="ex-main-btn"
                            :class="{ 'ex-main-disabled-btn': checkValidation() }">{{ $t('global.save') }}
                    </button>
                </div>
            </b-row>
        </div>
    </b-modal>
</template>
<script>
    import vue2Dropzone from 'vue2-dropzone';
    import 'vue2-dropzone/dist/vue2Dropzone.css';

    export default {
        name: 'AddDxfFileModal',
        components: {
            vueDropzone: vue2Dropzone,
        },
        props: {
            addFile: {
                type: Function,
                required: true,
            },
            updateFile: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                fileActions: false,
                inProgress: false,
                editFileId: null,
                uploadedFile: {},
                Scenario: {},
                defaultThumbnail: '/src/assets/img/dxf.png',
                dropzoneOptions: {
                    dictDefaultMessage: this.$i18n.t('scenario_wizard.dxf.file_upload_body'),
                    url: '/put',
                    autoProcessQueue: false,
                    uploadMultiple: false,
                    maxFiles: 1,
                    acceptedFiles: '.dxf',
                    maxFilesize: 100,
                },
                levelItems: [
                    [], [],
                ],
                groundType: null,
                assignedLevels: [
                    {
                        singleRangeLevel: 1,
                        levelItem: null,
                        levelItemFrom: null,
                        levelItemTo: null,
                    },
                ],
                specifyLevelUnderAbove: 1,
                partlyUndergroundNeg: null,
                partlyAboveUndergroundUnit: 'mm',
                doorLayerName: [],
                windowLayerName: [],
                wallLayerName: [],
                wallLayerNames: [
                    'E-WALL',
                    'F-WALL',
                    'G-WALL-123',
                ],
                windowLayerNames: [
                    'A-WIN-FULL-ABOVE-E',
                    'A-WIN-FULL-ABOVE-F',
                    'B-WIN',
                    'C-WIN-FULL-ABOVE-F',
                    'D-Win',
                ],
                doorLayerNames: [
                    'E-DOOR-1',
                    'F-DOOR',
                    'G-DOOR-123',
                    'H-DOOR',
                    'H-DOOR-5',
                    'H-DOOR-6',
                    'H-DOOR-7',
                    'H-DOOR-8',
                    'H-DOOR-9',
                ],
                unitItems: [
                    'm',
                    'mm',
                    'ft',
                    'in',
                ],
                dimensionDetails: {
                    ceilingHeight: {
                        unit: 'mm',
                        value: null,
                    },
                    doorHeight: {
                        unit: 'mm',
                        value: null,
                    },
                    windowHeight: {
                        unit: 'mm',
                        value: null,
                    },
                    windowSillHeight: {
                        unit: 'mm',
                        value: null,
                    },
                    getData: function (key) {
                        const value = this[key].value ? this[key].value : 0;
                        return value + this[key].unit;
                    },
                },
            };
        },
        created() {
            for (let i = 1; i <= 99; i += 1) {
                this.levelItems[0].push(i);
                this.levelItems[1].push(0 - i);
            }
        },
        beforeDestroy() {
//        this.$refs.myVueDropzone.destroy();
//        this.allowContinue(true);
        },
        methods: {
            open() {
                this.$refs.addDxfFileModal.show();
            },
            isHidden() {
                return !this.$refs.addDxfFileModal.is_show;
            },
            hide() {
                if (this.editFileId) {
                    this.setDefaultData();
                    this.editFileId = null;
                }
                this.$refs.addDxfFileModal.hide();
            },
            changeGroundType() {
                this.assignedLevels = this.assignedLevels.map((item) => {
                    item.levelItem *= -1;
                    item.levelItemFrom *= -1;
                    item.levelItemTo *= -1;
                    return item;
                });
            },
            onInputSpecifyLayerNames() {
                this.$nextTick(() => {
                    $('.chip--select-multi').removeClass('chip--selected');
                });
            },
            getToLevelItems(levelItemFrom) {
                const k = levelItemFrom > 0 ? 1 : -1;
                const levelItemFromK = levelItemFrom * k;
                let nextMaxItem = null;
                let max = 100 * k;
                nextMaxItem = this.assignedLevels.find(i => (i.singleRangeLevel === 1 && i.levelItem * k > levelItemFromK + 1)
                    || (i.singleRangeLevel === 2 && i.levelItemFrom * k > levelItemFromK + 1));
                if (nextMaxItem && nextMaxItem.singleRangeLevel === 1) {
                    max = nextMaxItem.levelItem;
                } else if (nextMaxItem) {
                    max = nextMaxItem.levelItemFrom;
                }
                return this.levelItems[this.groundType - 1]
                    .filter(item => (item * k > levelItemFromK && item * k < max * k));
            },
            filteredNames() {
                const wallLayers = this.wallLayerNames.filter(item => this.wallLayerName.indexOf(item) === -1);
                const doorLayers = this.doorLayerNames.filter(item => this.doorLayerName.indexOf(item) === -1);
                const windowLayers = this.windowLayerNames.filter(item => this.windowLayerName.indexOf(item) === -1);
                return wallLayers.concat(doorLayers, windowLayers);
            },
            filteredLevelItems(items, levelId) {
                const k = items[0] > 0 ? 1 : -1;
                return items.filter((level) => {
                    const levelK = level * k;
                    return !this.assignedLevels
                        .find((i, index) =>
                            ((i.singleRangeLevel === 1 && i.levelItem * k === levelK)
                                || (i.singleRangeLevel === 2 && i.levelItemFrom * k <= levelK && i.levelItemTo * k >= levelK))
                            && index !== levelId);
                });
            },
            rangeFilteredLevelItems(items, levelId) {
                const k = items[0] > 0 ? 1 : -1;
                return items.filter((level) => {
                    const levelK = level * k;
                    return !this.assignedLevels
                        .find((i, index) =>
                            ((i.singleRangeLevel === 1 && (i.levelItem * k === levelK || i.levelItem * k === levelK + 1))
                                || (i.singleRangeLevel === 2 && i.levelItemFrom * k <= levelK && i.levelItemTo * k >= levelK)
                                || (i.singleRangeLevel === 2 && i.levelItemFrom * k <= levelK + 1 && i.levelItemTo * k >= levelK + 1))
                            && index !== levelId);
                });
            },
            addLevels() {
                this.assignedLevels.push({
                    singleRangeLevel: 1,
                    levelItem: null,
                    levelItemFrom: null,
                    levelItemTo: null,
                });
            },
            deleteAssignedLevel(index) {
                this.assignedLevels.splice(index, 1);
            },
            checkSpecificLevel() {
                const level = this.groundType === 1 ? 1 : -1;
                return !!this.assignedLevels.find(item => (item.singleRangeLevel === 1 && item.levelItem === level) || (item.singleRangeLevel === 2 && item.levelItemFrom === level));
            },
            addDxfFile() {
                if (!this.checkValidation()) {
                    const fileData = {
                        groundType: this.groundType,
                        file: this.uploadedFile,
                        assignedLevels: this.assignedLevels.filter(level => (level.singleRangeLevel === 1 && level.levelItem) || (level.singleRangeLevel === 2 && level.levelItemFrom)),
                        showCollapse: false,
                        dimensionDetails: Object.assign({}, this.dimensionDetails),
                        specifyLevelUnderAbove: this.specifyLevelUnderAbove,
                        specificLevel: this.checkSpecificLevel(),
                        doorLayerName: this.doorLayerName,
                        windowLayerName: this.windowLayerName,
                        wallLayerName: this.wallLayerName,
                        partlyUndergroundNeg: this.partlyUndergroundNeg,
                        partlyAboveUndergroundUnit: this.partlyAboveUndergroundUnit,
                    };
                    if (this.editFileId) {
                        fileData.id = this.editFileId;
                        fileData.showCollapse = true;
                        this.updateFile(fileData);
                    } else {
                        fileData.id = Math.random().toString(36).substr(2, 9);
                        this.addFile(fileData);
                    }
                    this.setDefaultData();
                    this.editFileId = null;
                    this.hide();
                } else if (!this.uploadedFile.id) {
                    this.$toaster.show('Please upload a dxf file', 'error', 3000);
                } else {
                    const names = [];
                    if (!this.doorLayerName.length) {
                        names.push('doors');
                    }
                    if (!this.wallLayerName.length) {
                        names.push('walls');
                    }
                    if (!this.windowLayerName.length) {
                        names.push('windows');
                    }
                    const message = names.join('/');
                    if (message.length >= 5) {
                        this.$toaster.show(this.$i18n.t('scenario_wizard.dxf.validation_error.layer_names', {message}), 'error', 6000, true);
                    } else {
                        this.$toaster.show('All questions has to be answered!', 'error', 3000);
                    }
                }
            },
            checkValidation() {
                return !this.assignedLevels.find(item => (item.singleRangeLevel === 1 && item.levelItem) || (item.singleRangeLevel === 2 && item.levelItemFrom))
                    || this.checkLayerNames();
            },
            checkLayerNames() {
                return !this.wallLayerName.length || !this.doorLayerName.length || !this.windowLayerName.length;
            },
            levelItemFromChanged(assignedLevel, value) {
                if (value > 0 && (value >= assignedLevel.levelItemTo || !assignedLevel.levelItemTo)) {
                    assignedLevel.levelItemTo = value + 1;
                } else if (value < 0 && (value <= assignedLevel.levelItemTo || !assignedLevel.levelItemTo)) {
                    assignedLevel.levelItemTo = value - 1;
                }
            },
            triggerDropzone() {
                document.getElementById('dropzone2').click();
            },
            fileRemoved(file, error) {
                this.fileActions = false;
                if (this.uploadedFile.id) {
                    this.uploadedFile.delete().then((response) => {
                        console.log(response);
                    });
                }
                this.uploadedFile = {};
            },
            fileAdded(locFile) {
                const addEdFiles = this.$refs.myVueDropzone.getActiveFiles();
                addEdFiles.forEach((file) => {
                    this.$refs.myVueDropzone.removeFile(file);
                });
                this.fileActions = true;
                const self = this;
                const fileName = Date.now() + '_' + locFile.name;
                const file = new DB.File({
                    name: 'dxf_f/' + this.transformId() + '/' + fileName,
                    data: locFile,
                });
                file.upload().then((file) => {
                    self.uploadedFile = file;
                    self.uploadedFile.originalName = locFile.name;
                    console.log(file);
                }, (error) => {
                });
            },
            transformId: function () {
                return DB.User.me.id.split('/').slice(-1)[0];
            },
            uploadImgSuccess(file, response) {
            },
            setEditData(data) {
                this.uploadedFile = data.file;
                this.groundType = data.groundType;
                this.assignedLevels = data.assignedLevels.map(level => Object.assign({}, level));
                this.dimensionDetails = Object.assign({}, data.dimensionDetails);
                this.dimensionDetails.ceilingHeight = Object.assign({}, data.dimensionDetails.ceilingHeight);
                this.dimensionDetails.doorHeight = Object.assign({}, data.dimensionDetails.doorHeight);
                this.dimensionDetails.windowHeight = Object.assign({}, data.dimensionDetails.windowHeight);
                this.dimensionDetails.windowSillHeight = Object.assign({}, data.dimensionDetails.windowSillHeight);
                this.specifyLevelUnderAbove = data.specifyLevelUnderAbove;
                this.doorLayerName = data.doorLayerName;
                this.windowLayerName = data.windowLayerName;
                this.wallLayerName = data.wallLayerName;
                this.partlyUndergroundNeg = data.partlyUndergroundNeg;
                this.partlyAboveUndergroundUnit = data.partlyAboveUndergroundUnit;
                this.editFileId = data.id;
                this.open();
            },
            setDefaultData() {
                this.uploadedFile = {};
                this.assignedLevels = [{
                    singleRangeLevel: 1,
                    levelItem: null,
                    levelItemFrom: null,
                    levelItemTo: null,
                }];
                Object.keys(this.dimensionDetails).forEach((key) => {
                    if (typeof this.dimensionDetails[key] === 'object') {
                        this.dimensionDetails[key] = {value: null, unit: 'mm'};
                    }
                });
                this.doorLayerName = [];
                this.windowLayerName = [];
                this.wallLayerName = [];
                this.groundType = null;
                this.specifyLevelUnderAbove = 1;
                this.partlyAboveUndergroundUnit = 'mm';
                this.partlyUndergroundNeg = null;
                this.$refs.myVueDropzone.removeAllFiles();
            },
            getScenario() {
                this.Scenario = DB['EE.Aaa'].new();
                this.Scenario.idf_file = this.uploadedFile;
                return this.Scenario;
            },
        },
    };
</script>
