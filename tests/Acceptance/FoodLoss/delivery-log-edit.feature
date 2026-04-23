Feature:
    I want to make sure that the system is allow to access the details of a delivery log

    Scenario: As an owner, I can access to edit delivery logs
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given property "MyProperty" with id "prop-1" of account "my-account" exists
        Given recipient organization "FoodBank" with id "bank-1" of account "my-account" exists
        Given delivery log "Apples" with id "log-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/edit/log-1"
        When I submit a form with "productType:A new name" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Registro de entrega actualizado con éxito"

    Scenario: As an admin, I can't access to edit delivery logs
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyAccount" with id "my-account" exists
        Given delivery log "An item" with id "log-1" of account "my-account" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/delivery-logs/edit/log-1"
        Then the response status should be 403
