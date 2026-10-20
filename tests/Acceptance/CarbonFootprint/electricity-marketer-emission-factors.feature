Feature:
    I want to manage emission factors for all electricity marketers at once, year by year

    Scenario: As an admin, I see the latest year with data when entering the screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" and emission factor for year "2024" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I see a text "2024"
        Then I see a text "Marketer 1"
        Then I see a text "1.5"

    Scenario: As an admin, I can navigate to a specific year
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2020"
        Then the response status should be 200
        Then I see a text "2020"
        Then I see a text "Copiar factores"

    Scenario: As an admin, I can save emission factors in bulk for a year
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2024"
        When I submit a form with "[rows][0][co2eq]:2.5|[rows][0][co2]:2.0|[rows][0][ch4]:0.4|[rows][0][n2o]:0.6" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Factores de emisión actualizados con éxito"

    Scenario: As an owner, I can't access the emission factors screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors"
        Then the response status should be 403

    Scenario: As an admin, I can save a well-to-tank factor along with the emission factor
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2024"
        Then I see a text "Well-to-tank"
        When I submit a form with "[rows][0][co2eq]:2.5|[rows][0][co2]:2.0|[rows][0][ch4]:0.4|[rows][0][n2o]:0.6|[rows][0][wellToTankCo2eq]:0.61|[rows][0][wellToTankCo2]:0.6|[rows][0][wellToTankCh4]:0.01|[rows][0][wellToTankN2o]:0.01|[rows][0][wellToTankSource]:DEFRA" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Factores de emisión actualizados con éxito"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2024"
        Then I see a text "0.61"
        Then I see a text "DEFRA"

    Scenario: As an admin, a partial well-to-tank factor shows an error
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2024"
        When I submit a form with "[rows][0][co2eq]:2.5|[rows][0][co2]:2.0|[rows][0][ch4]:0.4|[rows][0][n2o]:0.6|[rows][0][wellToTankCo2eq]:0.61" and button "Guardar y salir"
        Then I see a text "Este valor no debería estar vacío"
        Then I do not see a text "Factores de emisión actualizados con éxito"

    Scenario: As an admin, I see the stored well-to-tank factor of each row
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" and emission factor with well-to-tank for year "2024" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/emission-factors/00000000-0000-0000-0000-000000000001/2024"
        Then the response status should be 200
        Then I see a text "0.61"
        Then I see a text "DEFRA"
