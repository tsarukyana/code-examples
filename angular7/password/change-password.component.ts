import {Component, OnInit} from '@angular/core';
import {UserService} from "@app/core/services";
import {FormBuilder, FormControl, FormGroup, Validators} from "@angular/forms";
import {NgxSmartModalService} from "ngx-smart-modal";
import {User} from "@app/core/models";
import {ComplexPasswordValidator, ConfirmPasswordValidator} from "@app/shared/validators";

@Component({
    templateUrl: 'change-password.component.html',
    styleUrls: ['./change-password.component.scss'],
})

export class ChangePasswordComponent implements OnInit {
    showNewPassword = false;
    showOldPassword = false;
    showVerifyPassword = false;
    user: User;
    isOldPasswordIncorrect: Boolean = false;
    passwordsNotEqual: Boolean = false;
    oldAndNewEqual: Boolean = false;

    changePasswordForm: FormGroup;

    constructor(
        private userService: UserService,
        private ngxSmartModalService: NgxSmartModalService,
        private formBuilder: FormBuilder,
    ) {
    }

    ngOnInit(): void {
        this.getCurrentUser();
        this.changePasswordForm = this.formBuilder.group({
            oldPassword: new FormControl('', [Validators.required]),
            password: new FormControl('', [Validators.required, ComplexPasswordValidator()]),
            confirmPassword: new FormControl('', [Validators.required]),
        }, {validator: ConfirmPasswordValidator()});
    }

    get form() {
        return this.changePasswordForm.controls;
    }

    changePassword() {
        let password = this.changePasswordForm.value;
        let email = this.user.email;
        let id = this.user._id;

        if (password.password === password.confirmPassword) {
            let changePassword = {
                email: email,
                password: password.oldPassword,
                newPassword: password.password,
                id: id
            };

            this.userService.changePassword(changePassword)
                .subscribe((res) => {
                    console.log("changePassword res", res);
                    if (res.status === 'SUCCESS' && res.payload) {
                        this.ngxSmartModalService.getModal('changePasswordMessage').open();
                        this.changePasswordForm.reset();
                    }
                })
        }
    }

    getCurrentUser(): void {
        this.userService.currentUser.subscribe((user) => {
            this.user = user;
        });

    }

    checkNewConfirmPasswords() {
        let password = this.changePasswordForm.value;
        this.passwordsNotEqual = password.password !== password.confirmPassword;
    }

    checkOldAndNewPasswords() {
        let password = this.changePasswordForm.value;
        this.oldAndNewEqual = password.password === password.oldPassword;
    }

    checkOldPassword() {
        let oldPassword = this.changePasswordForm.value.oldPassword;
        if (oldPassword) {
            let email = this.user.email;
            let id = this.user._id;
            this.userService.verifyPassword({
                email: email,
                password: oldPassword,
                id: id,
            }).subscribe((res) => {
                this.isOldPasswordIncorrect = !res.payload;
            });
        } else {
            this.isOldPasswordIncorrect = false;

        }
    }

}
