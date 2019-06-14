<template>
    <div>
        <nav id="myProjectsHeader" class="navbar navbar-expand-lg navbar-dark">
            <div class="container align-items-start">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#"></a>
                    <p>{{ $t('my_projects') }}</p>
                </div>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            {{user.first_name}} {{user.last_name}}
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                            <a class="dropdown-item" href="#">{{ $t('account_details') }}</a>
                            <a class="dropdown-item" v-on:click.prevent="$parent.logout" href="#">{{ $t('logout') }}</a>
                            <a class="dropdown-item" v-on:click.prevent="$parent.changeLang('en-us')" href="#">Lang
                                En</a>
                            <a class="dropdown-item" v-on:click.prevent="$parent.changeLang('de-ch')" href="#">Lang
                                De</a>
                        </div>
                    </li>
                </ul>
                <div class="ex-create-project">
                    <a v-b-modal.modalWizard v-on:click.prevent="" href="#">
                        <i></i>
                    </a>
                    <div v-if="userProjects.length === 0">
                        <span style="color:#00c3fa;">C</span><span style="color:#01c3f5;">r</span><span
                            style="color:#02c4ef;">e</span><span style="color:#02c4ea;">a</span><span
                            style="color:#03c5e4;">t</span><span style="color:#04c5df;">e</span><span
                            style="color:#05c6da;">&nbsp;</span><span style="color:#06c6d4;">y</span><span
                            style="color:#06c7cf;">o</span><span style="color:#07c7c9;">u</span><span
                            style="color:#08c7c4;">r</span><span style="color:#09c8bf;">&nbsp;</span><span
                            style="color:#0ac8b9;">f</span><span style="color:#0ac9b4;">i</span><span
                            style="color:#0bc9ae;">r</span><span style="color:#0ccaa9;">s</span><span
                            style="color:#0dcaa4;">t</span><span style="color:#0eca9e;">&nbsp;</span><span
                            style="color:#0ecb99;">p</span><span style="color:#0fcb93;">r</span><span
                            style="color:#10cc8e;">o</span><span style="color:#11cc89;">j</span><span
                            style="color:#12cd83;">e</span><span style="color:#12cd7e;">c</span><span
                            style="color:#13ce78;">t</span><span style="color:#14ce73;">.</span>
                        <figure class="triangle-with-shadow"></figure>
                    </div>
                </div>
            </div>
        </nav>
        <main class="ex-main-projects-list">
            <div class="container ex-prime">
                <section v-if="userProjects.length === 0" class="row">
                    <div class="col">
                        <p class="ex-empty-projects-text">{{ $t('empty_project_text') }}</p>
                    </div>
                </section>
                <section v-if="userProjects.length > 0">
                    <div class="row ex-search">
                        <div class="col-xs-12">
                            <i></i>
                        </div>
                    </div>
                    <div class="row ex-projects" v-for="i in Math.ceil(userProjects.length / 3)" :key="i">
                        <project-list ref="projectItem"
                                      :showDeleteProjectModal="showDeleteProjectModal"
                                      :updateProjects="updateProjects"
                                      v-bind:currentProject="index"
                                      v-for="index in userProjects.slice((i - 1) * 3, i * 3)" :key="index.id">
                        </project-list>
                    </div>
                </section>
            </div>
        </main>
        <b-modal ref="modalWizard" id="modalWizard" size="lg" hide-header hide-footer>
            <span v-if="isActivePrev" class="ex-prev" v-on:click="backToPrev"></span>
            <b-btn class="btn btn-light ex-close-btn" @click="hideModal">×</b-btn>
            <b-container>
                <b-row class="justify-content-center text-center">
                    <b-col class="col ex-title">{{ $t('create_new_project') }}</b-col>
                </b-row>
            </b-container>
            <project-form-wizard ref="projectFormWizard" :updateProjects="updateProjects" :allowPrevBtn="allowPrevBtn"
                                 :hidePrevBtn="hidePrevBtn"></project-form-wizard>
        </b-modal>
        <!-- Delete project modal -->
        <b-modal class="ex-delete-project-modal"
                 ref="modalProjectDelete"
                 :title="$t('delete_modal_header')"
                 header-text-variant="light"
                 header-bg-variant="dark"
                 centered>
            <div class="my-4 text-center">
                <p v-html="$tc('delete_project_message_text', 1)"></p>
                <p>{{ $tc('delete_project_message_text', 2) }}</p>
            </div>
            <div slot="modal-footer">
                <button type="button" class="btn btn-danger" @click="deleteCurrentProject()">{{ $t('yes') }}</button>
                <button type="button" class="btn btn-secondary" @click="hideCurrentProjectDeleteModal()">{{ $t('no')
                    }}
                </button>
            </div>
        </b-modal>
    </div>
</template>
<script>
    import ProjectList from "../components/ProjectList.vue"
    import ProjectFormWizard from "../components/ProjectFormWizard.vue"

    export default {
        name: 'Home',
        components: {
            ProjectList,
            ProjectFormWizard,
        },
        created: function () {
            const self = this;
            DB["ecoglobe.project.NewBuildingProject"].find().equal('owner', DB.User.me).resultList(function (response) {
                self.userProjects = response;
            });
            window.addEventListener('keyup', this.modalWizardOnEnter);
        },
        beforeDestroy() {
            window.removeEventListener('keyup', this.modalWizardOnEnter);
        },
        data() {
            return {
                currentProjectData: {},
                isActivePrev: false,
                user: {
                    first_name: DB.User.me.first_name,
                    last_name: DB.User.me.last_name,
                },
                userProjects: [],
            }
        },
        methods: {
            hideModal() {
                this.$refs.modalWizard.hide();
            },
            modalWizardOnEnter(event) {
                if (this.$refs.modalWizard.is_show && event.keyCode === 13) {
                    this.$refs.projectFormWizard.emitNextTab();
                }
            },
            updateProjects(project) {
                this.userProjects.push(project);
            },
            allowPrevBtn() {
                this.isActivePrev = true;
            },
            hidePrevBtn() {
                this.isActivePrev = false;
            },
            backToPrev() {
                this.$refs.projectFormWizard.backPrevTab();
            },
            showDeleteProjectModal(response) {
                this.currentProjectData = response;
                this.$refs.modalProjectDelete.show();
            },
            hideCurrentProjectDeleteModal() {
                this.$refs.modalProjectDelete.hide();
            },
            deleteCurrentProject() {
                let vm = this;
                vm.currentProjectData.delete().then((file) => {
                    vm.deletedProject = true;
                    vm.$refs.modalProjectDelete.hide();
                    DB["ecoglobe.project.NewBuildingProject"].find().equal('owner', DB.User.me).resultList(function (response) {
                        vm.userProjects = response;
                    });
                }, (error) => {
                    console.log('error', error.response);
                });
            },
        }
    };
</script>
