Feature:
    I want to make sure that the system is allow to access the details of an electricity mix

    Scenario: As an admin, I can access to edit electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/edit/333"
        When I submit a form with "countryId:C-ES" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Mix eléctrico actualizado con éxito"

    Scenario: As an admin, I see the emission factors table when editing an electricity mix
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"

    Scenario: As an admin, I can't move an electricity mix to a country that already has one
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given country "Francia" with iso "FR" and id "C-FR" exists
        Given electricity mix for country "C-ES" with id "333" exists
        Given electricity mix for country "C-FR" with id "444" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/edit/333"
        When I submit a form with "countryId:C-FR" and button "Guardar y salir"
        Then the response status should be 200
        Then I see a text "Ya existe un mix eléctrico para ese país"
        When I go to "/es/admin/electricity-mixes"
        Then I see a text "España"

    Scenario: As an owner, I can't access to edit electricity mixes
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/electricity-mixes/edit/333"
        Then the response status should be 403
