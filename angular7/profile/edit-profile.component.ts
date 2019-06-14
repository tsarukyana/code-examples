import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ConfigService } from '@app/core/services';
import { UserService, ZipService } from "@app/core/services";
import { FormBuilder, FormGroup, Validators, FormControl, FormArray } from '@angular/forms';
import { NgxSmartModalService } from 'ngx-smart-modal';
import { User, Address } from "@app/core/models";
import { AllergiesPreferencesComponent} from "@app/shared/components";

@Component({
    templateUrl: 'edit-profile.component.html',
    styleUrls: ['./edit-profile.component.scss'],
})
export class EditProfileComponent implements OnInit {
    updateUserValidate: FormGroup;
    loading: boolean;
    allergies = [];
    preferences = [];
    user: User;
    anyAllergies: boolean;
    anyPreferences = false;
    editedUser = null;
    userProfileForm: FormGroup;
    newAllergyName: string;
    newPreferenceName: string;

    @ViewChild('allergyAndPref') allergyAndPreferences: AllergiesPreferencesComponent;

    constructor(
        private route: ActivatedRoute,
        private configService: ConfigService,
        private userService: UserService,
        private ngxSmartModalService: NgxSmartModalService,
        private formBuilder: FormBuilder,
        private zipService: ZipService
    ) {

    }

    ngOnInit() {
        this.getAllergiesAndPreferences();
        this.getCurrentUser();
        this.initForm();
    }

    get form() {
        return this.userProfileForm.controls;
    }

    get address(): Address {
        let userAddress = this.user.address;
        return userAddress && userAddress.length ? userAddress[0] : new Address();
    }

    get userAllergies() {
        let allergies = this.user.allergies;
        return allergies && allergies.length ? allergies : this.allergies;
    }

    get userPreferences() {
        let preferences = this.user.preferences;
        return preferences && preferences.length ? preferences : this.preferences;
    }

    initForm() {
        const formControlsAllergies = this.userAllergies.map((control) => new FormControl(!!control.checked));
        this.anyAllergies = !!(formControlsAllergies.find( (checked) => checked.value));

        const formControlsPreferences = this.userPreferences.map((control) => new FormControl(!!control.checked));
        this.anyPreferences = !!(formControlsPreferences.find( (checked) => checked.value));

        this.userProfileForm = this.formBuilder.group({
            firstName: new FormControl(this.user.firstName, [Validators.required, Validators.maxLength(30)]),
            lastName: new FormControl(this.user.lastName, [Validators.required, Validators.maxLength(30)]),
            street: new FormControl(this.address.street, [Validators.required]),
            city: new FormControl(this.address.city, [Validators.required]),
            state: new FormControl(this.address.state, [Validators.required]),
            zip: new FormControl(this.address.zip, [Validators.required]),
            allergies: new FormArray(formControlsAllergies),
            preferences: new FormArray(formControlsPreferences)
        });
    }

    getCityAndStateByZip() {
        this.zipService.getCityAndStateByZip(this.form.zip.value)
            .subscribe((res) => {
                if (res) {
                    this.form.city.setValue(res.city);
                    this.form.state.setValue(res.state);
                } else {
                    this.form.zip.setErrors({'invalid': true});
                }
            });
    }

    getAllergiesAndPreferences(): void {
        this.allergies = this.configService.allergyList;
        this.preferences = this.configService.preferenceList;
    }

    getCurrentUser(): void {
        this.userService.currentUser.subscribe((user) => {
            this.user = user;
        });
    }


    isAllergySelected() {
        return this.form.allergies.value.find((checked) => checked);
    }

    isPreferenceSelected() {
        return this.form.preferences.value.find((checked) => checked);
    }



    updateUserWithEdited() {
        this.loading = true;

        this.editedUser = this.userProfileForm.value;
        this.editedUser.address = [{
            street: this.editedUser.street,
            city: this.editedUser.city,
            state: this.editedUser.state,
            zip: this.editedUser.zip
        }];

        this.loading = false;



        const updateUserInfo = {
            _id: this.user._id,
            firstName: this.editedUser.firstName,
            lastName: this.editedUser.lastName,
            address: this.editedUser.address,
            phone: this.editedUser.phone,
            allergies: this.allergyAndPreferences.localData.suggestedAllergies,
            preferences: this.allergyAndPreferences.localData.suggestedPreferences,
            preferredPricePoint: this.editedUser.preferredPricePoint,
        };

        this.userService.updateUser(updateUserInfo)
            .subscribe((res) => {
                if (res.status === "SUCCESS") {
                    this.userService.setUser(res.payload);
                }

                this.loading = false;
                this.ngxSmartModalService.getModal('editProfileMessage').open();
            }, error => {
                console.error(error);
                this.loading = false;
            })
    }

}
