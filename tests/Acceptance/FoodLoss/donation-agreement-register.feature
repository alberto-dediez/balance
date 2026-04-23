Feature:
    I want to make sure that the software allow to register a donation agreement

    Scenario: As an admin, I can't access to register new donation agreements
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/donation-agreements/register"
        Then the response status should be 403

    Scenario: As an owner, I can register a new donation agreement
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Org 1" with id "org1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/donation-agreements/register"
        Then the response status should be 200
        Given a dummy PDF file exists in "tests/Acceptance/FoodLoss/test.pdf"
        When I submit a form with "recipientOrganizationId:org1|startDate:2023-01-01|expirationDate:2024-01-01" and button "Registrar"
        Then I see a text "Este valor no debería estar vacío."
        When I attach the file "tests/Acceptance/FoodLoss/test.pdf" to "pdfFile"
        When I submit a form with "recipientOrganizationId:org1|startDate:2023-01-01|expirationDate:2024-01-01" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Convenio de donación creado correctamente"

    Scenario: As normal user I can't register new donation agreements
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/donation-agreements/register"
        Then the response status should be 403
