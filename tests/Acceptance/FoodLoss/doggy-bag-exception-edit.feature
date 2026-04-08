Feature:
    I want to make sure the system allows editing a Doggy Bag exception

    Scenario: As an owner, I can access to edit my Doggy Bag exception
        Given account "mycompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given critical point "Buffet" with id "cp1" of account "mycompany" exists
        Given doggy bag exception "Excepción buffet" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-exceptions/edit/333"
        When I submit a form with "description:Nueva razón|criticalPointId:cp1" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Excepción Doggy Bag actualizada con éxito"

    Scenario: As an owner, I cannot access to edit a Doggy Bag exception from another account
        Given account "mycompany" with id "mycompany" exists
        Given account "othercompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag exception "Excepción ajena" with id "444" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-exceptions/edit/444"
        Then the response status should be 403
