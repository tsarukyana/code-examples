<template>
    <div class="ex-file-other ex-file-dxf">
        <b-row class="justify-content-center">
            <b-col class="col-xl-12">
                <p class="ex-step-title">{{ $t('scenario_wizard.dxf.header') }}</p>
                <h5 class="ex-step-subtitle">{{ $t('scenario_wizard.dxf.sub_headline') }}</h5>
                <!--First Media Start-->
                <template v-for="(fileData, index) in orderedFiles">
                    <div class="media" :key="index">
                        <i class="arrow" @click="fileData.showCollapse = !fileData.showCollapse"
                           :class="fileData.showCollapse ? 'collapsed' : null"
                           :aria-controls="'collapse_' + fileData.id + ' collapseFooter_' + fileData.id"
                           :aria-expanded="fileData.showCollapse ? 'true' : 'false'"/>
                        <img class="mr-5" src="/src/assets/img/dxf.png" alt="Generic placeholder image">
                        <div class="media-body">
                            <h5 class="mt-0">{{ fileData.file.originalName }}</h5>
                            <p class="ex-step-used-subtitle mb-3">{{ $tc('scenario_wizard.dxf.assigned_level',2) }}:</p>
                            <div class="mb-4">
                                <template
                                        v-for="(level, k) in orderedLevels(fileData.assignedLevels, fileData.groundType)">
                                    <span class="ex-levels-range mr-2" :key="k+ 'span'"
                                          v-if="level.singleRangeLevel === 2">
                                        {{ level.levelItemFrom }} {{ $t('global.to') }} {{ level.levelItemTo }}
                                    </span>
                                    <span class="ex-levels-single mr-2" :key="k+ 'span'"
                                          v-else>{{ level.levelItem }}</span>
                                </template>
                            </div>
                            <b-collapse :id="'collapse_' + fileData.id" v-model="fileData.showCollapse">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <p class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.ceiling_height')
                                            }}</p>
                                        <p class="ex-details-sizes">{{
                                            fileData.dimensionDetails.getData('ceilingHeight') }}</p>
                                    </div>
                                    <div class="col-lg-3">
                                        <p class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.door_height') }}</p>
                                        <p class="ex-details-sizes">{{ fileData.dimensionDetails.getData('doorHeight')
                                            }}</p>
                                    </div>
                                    <div class="col-lg-3">
                                        <p class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.window_height')
                                            }}</p>
                                        <p class="ex-details-sizes">{{ fileData.dimensionDetails.getData('windowHeight')
                                            }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3">
                                        <p class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.window_sill_height')
                                            }}</p>
                                        <p class="ex-details-sizes">{{
                                            fileData.dimensionDetails.getData('windowSillHeight') }}</p>
                                    </div>
                                    <div class="col-lg-3" v-if="fileData.specificLevel">
                                        <p class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.details_level', {
                                            level: fileData.groundType == 1 ? 1 : -1 }) }}</p>
                                        <p class="ex-details-sizes">{{ getSpecifyLevelMsg(fileData) }}</p>
                                    </div>
                                </div>
                            </b-collapse>
                        </div>
                    </div>
                    <b-collapse :key="index+ 'col'" :id="'collapseFooter_' + fileData.id"
                                v-model="fileData.showCollapse">
                        <div class="media-footer">
                            <div class="row">
                                <div class="col-lg-3">
                                    <a :href="fileData.file.url" download>
                                        <i class="downloading-down-arrow"/>
                                        <span>{{ $t('global.download') }} DXF</span>
                                    </a>
                                </div>
                                <div class="col-lg-3">
                                    <i class="eye"/>
                                    <span>{{ $t('global.view') }} DXF</span>
                                </div>
                                <div class="col-lg-3">
                                    <span role="button" @click="$refs.addDxfFileModal.setEditData(fileData)">
                                        <i class="edit-text"/>
                                        <span>{{ $t('global.edit_details') }}</span>
                                    </span>
                                </div>
                                <div class="col-lg-3">
                                    <span role="button" @click="showDeleteFileModal(index)">
                                        <i class="delete"/>
                                        <span>{{ $t('global.delete_file') }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </b-collapse>
                    <div class="ex-border-bottom" :key="index+ 'div'"/>
                </template>
                <!--First Media End-->
                <button class="ex-cancel-btn mb-5" @click="addDxfFile">+ {{ $t('scenario_wizard.dxf.add_button') }}
                </button>
                <hr class="mb-4">
                <p class="ex-unit-used-title">{{ $t('scenario_wizard.dxf.unit_used[0]') }}</p>
                <h5 class="ex-step-used-subtitle">{{ $t('scenario_wizard.dxf.unit_used[1]') }}</h5>
                <label class="ex-unit-select-label">{{ $t('global.unit') }}</label>
                <div class="ex-unit-select mb-4">
                    <v-select :items="unitItems"
                              v-model="unitUsed"
                              :auto="true"
                              single-line
                              :hide-details="true"
                              @change="selectCallback"
                    >
                    </v-select>
                </div>
                <p class="ex-unit-used-title">{{ $t('scenario_wizard.dxf.floor_and_roof[0]') }}</p>
                <h5 class="ex-step-used-subtitle mb-3">{{ $t('scenario_wizard.dxf.floor_and_roof[1]') }}</h5>
                <div class="row mb-5">
                    <div class="col-lg-3">
                        <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.floor_thickness') }}</label>
                        <input type="number" min="0" class="ex-input-floor-roof" name="">
                        <div class="ex-unit-select">
                            <v-select :items="unitItems"
                                      v-model="unitFloorThickness"
                                      :auto="true"
                                      single-line
                                      :hide-details="true"
                                      @change="selectCallback"
                            >
                            </v-select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.basement_floor_thickness')
                            }}</label>
                        <input type="number" min="0" class="ex-input-floor-roof" name="">
                        <div class="ex-unit-select">
                            <v-select :items="unitItems"
                                      v-model="unitBasementFloor"
                                      :auto="true"
                                      single-line
                                      :hide-details="true"
                                      @change="selectCallback"
                            >
                            </v-select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <label class="ex-unit-select-label">{{ $t('scenario_wizard.dxf.roof_thickness') }}</label>
                        <input type="number" min="0" class="ex-input-floor-roof" name="">
                        <div class="ex-unit-select">
                            <v-select :items="unitItems"
                                      v-model="unitRoofThickness"
                                      :auto="true"
                                      single-line
                                      :hide-details="true"
                                      @change="selectCallback"
                            >
                            </v-select>
                        </div>
                    </div>
                </div>
                <add-dxf-file-modal ref="addDxfFileModal" :add-file="addFile"
                                    :update-file="updateFileData"></add-dxf-file-modal>
            </b-col>
        </b-row>
        <b-modal ref="deleteFileModal" class="ex-delete-scenario-modal"
                 :title="$t('delete_scenario_modal.header')"
                 hide-header
                 :no-close-on-backdrop="true"
                 centered>
            <div class="my-4 text-center">
                <p v-html="$tc('scenario_wizard.dxf.file_options', 2)"/>
                <p>{{ $t('scenario_wizard.dxf.delete_file_body') }}</p>
            </div>
            <div slot="modal-footer">
                <button type="button" class="ex-cancel-btn" @click="hideDeleteFileModal">
                    {{ $t('global.cancel') }}
                </button>
                <button type="button" class="ex-delete-btn" @click="deleteDxfFile">
                    {{ $t('global.yes') }}, {{ $tc('scenario_wizard.dxf.file_options', 2) }}
                </button>
            </div>
        </b-modal>
    </div>
</template>
<script>
    import _ from 'lodash/collection';
    import AddDxfFileModal from './AddDxfFileModal.vue';

    export default {
        name: 'ScenarioUploadFileDxf',
        components: {AddDxfFileModal},
        props: {
            allowContinue: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                DXFFileScenario: {},
                deleteFileIndex: null,
                addedFiles: [],
                defaultThumbnail: '/src/assets/img/thumb-placeholder.png',
                unitItems: [
                    'm',
                    'mm',
                    'ft',
                    'in',
                ],
                unitUsed: 'mm',
                unitRoofThickness: 'mm',
                unitBasementFloor: 'mm',
                unitFloorThickness: 'mm',
            };
        },
        computed: {
            orderedFiles() {
                return _.orderBy(this.addedFiles, (e) => {
                    const levels = _.orderBy(e.assignedLevels, (i) => {
                        if (i.singleRangeLevel === 1) {
                            i.newLevelItem = i.levelItem;
                            return i.levelItem;
                        }
                        i.newLevelItem = i.levelItemFrom;
                        return i.levelItemFrom;
                    }, ['asc']);
                    return levels[0].newLevelItem;
                }, ['desc']);
            },
        },
        methods: {
            showDeleteFileModal(index) {
                this.deleteFileIndex = index;
                this.$refs.deleteFileModal.show();
            },
            hideDeleteFileModal() {
                this.$refs.deleteFileModal.hide();
            },
            isModalHidden() {
                return this.$refs.addDxfFileModal.isHidden();
            },
            deleteDxfFile() {
                this.addedFiles[this.deleteFileIndex].file.delete().then();
                this.addedFiles.splice(this.deleteFileIndex, 1);
                if (this.addedFiles.length === 0) {
                    this.allowContinue(false);
                }
                this.$refs.deleteFileModal.hide();
            },
            selectCallback(value) {
            },
            addDxfFile() {
                this.$refs.addDxfFileModal.open();
                if (this.addedFiles.length === 0) {
                    this.allowContinue(false);
                }
            },
            addFile(fileData) {
                this.addedFiles.push(fileData);
                this.allowContinue(true);
            },
            updateFileData(newData) {
                const fileData = this.addedFiles.find(item => item.id === newData.id);
                this.addedFiles.splice(this.addedFiles.indexOf(fileData), 1, newData);
            },
            downloadFile(file) {
            },
            orderedLevels(levels, groundType) {
                const orderType = groundType === 1 ? 'asc' : 'desc';
                return _.orderBy(levels, i => (i.singleRangeLevel === 1 ? i.levelItem : i.levelItemFrom), [orderType]);
            },
            checkValidation() {
                const levels = [];
                let errorExist = false;
                this.addedFiles.forEach((data) => {
                    data.assignedLevels.forEach((l) => {
                        if (l.singleRangeLevel === 1) {
                            levels.push(l.levelItem);
                        } else if (l.levelItemFrom > 0) {
                            for (let i = l.levelItemFrom; i <= l.levelItemTo; i += 1) {
                                levels.push(i);
                            }
                        } else if (l.levelItemFrom < 0) {
                            for (let i = l.levelItemTo; i <= l.levelItemFrom; i += 1) {
                                levels.push(i);
                            }
                        }
                    });
                });
                const orderedLevels = _.orderBy(levels, i => i, ['asc']);
                orderedLevels.forEach((value, index) => {
                    if (value + 1 !== orderedLevels[index + 1] && index !== (orderedLevels.length - 1)) {
                        errorExist = true;
                    }
                });
                if (!errorExist) {
                    errorExist = _.find(_.countBy(levels, item => item), countL => countL > 1);
                    if (errorExist) {
                        this.$toaster.show(this.$i18n.t('scenario_wizard.dxf.validation_error.more_than_one'), 'error', 10000, true);
                    }
                } else {
                    this.$toaster.show(this.$i18n.t('scenario_wizard.dxf.validation_error.forgot_to_assign'), 'error', 10000, true);
                }
                return !errorExist;
            },
            getSpecifyLevelMsg(fileData) {
                if (fileData.groundType === 1) {
                    if (fileData.specifyLevelUnderAbove === 1) {
                        return this.$i18n.t('scenario_wizard.dxf.completely_aboveground');
                    }
                    return this.$i18n.t('scenario_wizard.dxf.partly_aboveground');
                }
                if (fileData.groundType === 2) {
                    if (fileData.specifyLevelUnderAbove === 1) {
                        return this.$i18n.t('scenario_wizard.dxf.completely_underground');
                    }
                }
                return this.$i18n.t('scenario_wizard.dxf.partly_underground');
            },
            getScenario() {
                return null;
            },
        },
    };
</script>
