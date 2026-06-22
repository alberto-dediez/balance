Feature:
    I want to make sure that the software allows cancelling the signature process for a training attendance

    Scenario: As an owner, I can access the cancel signature page for an attendance in signature process
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" with pending signature request "sig-1" of session "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can cancel the signature process
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" with pending signature request "sig-1" of session "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        When I submit a form with "" and button "Confirmar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha cancelado el proceso de firma correctamente"

    Scenario: As an owner, accessing an attendance not in signature process redirects with a warning
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given employee "Alice" with id "emp-1" of account "mycompany" exists
        Given training attendance "att-1" for employee "emp-1" of session "ts-1" of account "mycompany" at "path/sheet.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Esta asistencia no tiene un proceso de firma pendiente"

    Scenario: As an admin, I cannot access the cancel signature page
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" with pending signature request "sig-1" of session "ts-1" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        Then the response status should be 403

    Scenario: As a normal user, I cannot access the cancel signature page
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" with pending signature request "sig-1" of session "ts-1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        Then the response status should be 403

    Scenario: As an owner of a different account, I cannot access the cancel signature page
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "othercompany" exists
        Given training attendance "att-1" with pending signature request "sig-1" of session "ts-1" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/cancel-signature"
        Then the response status should be 403
