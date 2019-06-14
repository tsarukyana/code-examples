import {Component, OnInit} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';
import {UserService} from "@app/core/services";
import {FormBuilder, FormControl, FormGroup, Validators} from "@angular/forms";
import {NgxSmartModalService} from "ngx-smart-modal";
import {User} from "@app/core/models";
import {HttpClient} from '@angular/common/http';
import {MailerService} from "@app/core/services";
import {ConfirmEmailValidator, UniqueEmailValidator} from "@app/shared/validators";

@Component({
    templateUrl: 'change-email.component.html',
    styleUrls: ['./change-email.component.scss'],
})

export class ChangeEmailComponent implements OnInit {

    isOldEmailIncorrect: boolean = false;
    user: User;
    changeEmailForm: FormGroup;

    constructor(
        private userService: UserService,
        private ngxSmartModalService: NgxSmartModalService,
        private formBuilder: FormBuilder,
        private http: HttpClient,
        private mailerService: MailerService,
        private router: Router,) {
    }

    ngOnInit(): void {
        this.getCurrentUser();
        this.changeEmailForm = this.formBuilder.group({
            oldEmail: new FormControl('', [Validators.required, Validators.email]),
            newEmail: new FormControl('', [Validators.required, Validators.email], [UniqueEmailValidator(this.userService)]),
            confirmEmail: new FormControl('', [Validators.required]),
        }, {validator: ConfirmEmailValidator()});
    }

    get form() {
        return this.changeEmailForm.controls;
    }


    getCurrentUser(): void {
        this.userService.currentUser.subscribe((user) => {
            this.user = user;
        });
    }

    EmailIsCorrectOrNot() {
        let enteredEmail = this.changeEmailForm.value.oldEmail;
        if (typeof (enteredEmail) != 'undefined') {
            this.isOldEmailIncorrect = enteredEmail.toUpperCase() != this.user.email.toUpperCase();
        }
    }

    redirect() {
        this.router.navigate(['/']);
    }

    changeEmail() {
        let changeEmail = this.changeEmailForm.value;

        this.isOldEmailIncorrect = changeEmail.oldEmail.toUpperCase() != this.user.email.toUpperCase();
        if (!this.isOldEmailIncorrect) {
            // console.log("Change email: " + changeEmail.oldEmail + " : " + changeEmail.newEmail);

            // Change email in the database
            this.userService.changeEmail({
                oldEmail: changeEmail.oldEmail,
                newEmail: changeEmail.newEmail
            })
                .subscribe((changeEmailResp) => {
                    console.log("changeEmailResp", changeEmailResp);
                    if (changeEmailResp.status == "SUCCESS") {
                        this.user.email = changeEmail.newEmail;
                        this.ngxSmartModalService.getModal('changeEmailMessage').open();
                        this.changeEmailForm.reset();

                        this.mailerService.alertUserEmailChanged(changeEmail.oldEmail, changeEmail.newEmail);
                    } else {
                        this.ngxSmartModalService.getModal('changeEmailErrorMessage').open();
                    }
                })
        }
    };

}
