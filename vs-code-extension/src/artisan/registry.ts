import * as vscode from "vscode";
import { runArtisanCommand } from "@src/commands/artisan";

import { CastMakeCommand } from "./commands/CastMakeCommand";
import { ChannelMakeCommand } from "./commands/ChannelMakeCommand";
import { ClassMakeCommand } from "./commands/ClassMakeCommand";
import { CommandMakeCommand } from "./commands/CommandMakeCommand";
import { ComponentMakeCommand } from "./commands/ComponentMakeCommand";
import { ControllerMakeCommand } from "./commands/ControllerMakeCommand";
import { EnumMakeCommand } from "./commands/EnumMakeCommand";
import { EventMakeCommand } from "./commands/EventMakeCommand";
import { ExceptionMakeCommand } from "./commands/ExceptionMakeCommand";
import { FactoryMakeCommand } from "./commands/FactoryMakeCommand";
import { InterfaceMakeCommand } from "./commands/InterfaceMakeCommand";
import { JobMakeCommand } from "./commands/JobMakeCommand";
import { JobMiddlewareMakeCommand } from "./commands/JobMiddlewareMakeCommand";
import { ListenerMakeCommand } from "./commands/ListenerMakeCommand";
import { LivewireMakeCommand } from "./commands/LivewireMakeCommand";
import { MailMakeCommand } from "./commands/MailMakeCommand";
import { MiddlewareMakeCommand } from "./commands/MiddlewareMakeCommand";
import { MigrationMakeCommand } from "./commands/MigrationMakeCommand";
import { ModelMakeCommand } from "./commands/ModelMakeCommand";
import { NotificationMakeCommand } from "./commands/NotificationMakeCommand";
import { ObserverMakeCommand } from "./commands/ObserverMakeCommand";
import { PolicyMakeCommand } from "./commands/PolicyMakeCommand";
import { ProviderMakeCommand } from "./commands/ProviderMakeCommand";
import { RequestMakeCommand } from "./commands/RequestMakeCommand";
import { ResourceMakeCommand } from "./commands/ResourceMakeCommand";
import { ScopeMakeCommand } from "./commands/ScopeMakeCommand";
import { SeederMakeCommand } from "./commands/SeederMakeCommand";
import { TestMakeCommand } from "./commands/TestMakeCommand";
import { TraitMakeCommand } from "./commands/TraitMakeCommand";
import { ViewMakeCommand } from "./commands/ViewMakeCommand";
import { MigrateCommand } from "./commands/MigrateCommand";
import { MigrateFreshCommand } from "./commands/MigrateFreshCommand";
import { MigrateRefreshCommand } from "./commands/MigrateRefreshCommand";
import { MigrateRollbackCommand } from "./commands/MigrateRollbackCommand";
import { MigrateStatusCommand } from "./commands/MigrateStatusCommand";
import { TinkerCommand } from "./commands/TinkerCommand";
import { DbCommand } from "./commands/DbCommand";
import { RouteListCommand } from "./commands/RouteListCommand";
import { DbShowCommand } from "./commands/DbShowCommand";
import { PailCommand } from "./commands/PailCommand";
import { AuthClearResetsCommand } from "./commands/AuthClearResetsCommand";
import { DbSeedCommand } from "./commands/DbSeedCommand";
import { DbTableCommand } from "./commands/DbTableCommand";
import { DbWipeCommand } from "./commands/DbWipeCommand";
import { EventListCommand } from "./commands/EventListCommand";
import { KeyGenerateCommand } from "./commands/KeyGenerateCommand";
import { ModelShowCommand } from "./commands/ModelShowCommand";
import { PestDatasetCommand } from "./commands/PestDatasetCommand";
import { QueueClearCommand } from "./commands/QueueClearCommand";
import { QueueFailedCommand } from "./commands/QueueFailedCommand";
import { QueueFlushCommand } from "./commands/QueueFlushCommand";
import { QueueForgetCommand } from "./commands/QueueForgetCommand";
import { QueueRetryCommand } from "./commands/QueueRetryCommand";
import { ScheduleListCommand } from "./commands/ScheduleListCommand";
import { ScheduleTestCommand } from "./commands/ScheduleTestCommand";
import { SchemaDumpCommand } from "./commands/SchemaDumpCommand";
import { WayfinderGenerateCommand } from "./commands/WayfinderGenerateCommand";
import { CacheClearCommand } from "./commands/CacheClearCommand";
import { VendorPublishCommand } from "./commands/VendorPublishCommand";

const artisanMakeCommands = {
    "laravelLegacy.artisan.make.cast": CastMakeCommand,
    "laravelLegacy.artisan.make.channel": ChannelMakeCommand,
    "laravelLegacy.artisan.make.class": ClassMakeCommand,
    "laravelLegacy.artisan.make.command": CommandMakeCommand,
    "laravelLegacy.artisan.make.component": ComponentMakeCommand,
    "laravelLegacy.artisan.make.controller": ControllerMakeCommand,
    "laravelLegacy.artisan.make.enum": EnumMakeCommand,
    "laravelLegacy.artisan.make.event": EventMakeCommand,
    "laravelLegacy.artisan.make.exception": ExceptionMakeCommand,
    "laravelLegacy.artisan.make.factory": FactoryMakeCommand,
    "laravelLegacy.artisan.make.interface": InterfaceMakeCommand,
    "laravelLegacy.artisan.make.job": JobMakeCommand,
    "laravelLegacy.artisan.make.job-middleware": JobMiddlewareMakeCommand,
    "laravelLegacy.artisan.make.listener": ListenerMakeCommand,
    "laravelLegacy.artisan.make.livewire": LivewireMakeCommand,
    "laravelLegacy.artisan.make.mail": MailMakeCommand,
    "laravelLegacy.artisan.make.middleware": MiddlewareMakeCommand,
    "laravelLegacy.artisan.make.migration": MigrationMakeCommand,
    "laravelLegacy.artisan.make.model": ModelMakeCommand,
    "laravelLegacy.artisan.make.notification": NotificationMakeCommand,
    "laravelLegacy.artisan.make.observer": ObserverMakeCommand,
    "laravelLegacy.artisan.make.policy": PolicyMakeCommand,
    "laravelLegacy.artisan.make.provider": ProviderMakeCommand,
    "laravelLegacy.artisan.make.request": RequestMakeCommand,
    "laravelLegacy.artisan.make.resource": ResourceMakeCommand,
    "laravelLegacy.artisan.make.scope": ScopeMakeCommand,
    "laravelLegacy.artisan.make.seeder": SeederMakeCommand,
    "laravelLegacy.artisan.make.test": TestMakeCommand,
    "laravelLegacy.artisan.make.trait": TraitMakeCommand,
    "laravelLegacy.artisan.make.view": ViewMakeCommand,
};

export const registerArtisanMakeCommands = () => {
    return Object.entries(artisanMakeCommands).map(([name, command]) => {
        return vscode.commands.registerCommand(name, (uri: vscode.Uri) => {
            runArtisanCommand(command, uri);
        });
    });
};

const artisanCommands = {
    "laravelLegacy.artisan.migrate": MigrateCommand,
    "laravelLegacy.artisan.migrateFresh": MigrateFreshCommand,
    "laravelLegacy.artisan.migrateRefresh": MigrateRefreshCommand,
    "laravelLegacy.artisan.migrateRollback": MigrateRollbackCommand,
    "laravelLegacy.artisan.migrateStatus": MigrateStatusCommand,
    "laravelLegacy.artisan.tinker": TinkerCommand,
    "laravelLegacy.artisan.db": DbCommand,
    "laravelLegacy.artisan.dbShow": DbShowCommand,
    "laravelLegacy.artisan.dbSeed": DbSeedCommand,
    "laravelLegacy.artisan.dbTable": DbTableCommand,
    "laravelLegacy.artisan.dbWipe": DbWipeCommand,
    "laravelLegacy.artisan.pail": PailCommand,
    "laravelLegacy.artisan.authClearResets": AuthClearResetsCommand,
    "laravelLegacy.artisan.eventList": EventListCommand,
    "laravelLegacy.artisan.keyGenerate": KeyGenerateCommand,
    "laravelLegacy.artisan.modelShow": ModelShowCommand,
    "laravelLegacy.artisan.pestDataset": PestDatasetCommand,
    "laravelLegacy.artisan.queueClear": QueueClearCommand,
    "laravelLegacy.artisan.queueFailed": QueueFailedCommand,
    "laravelLegacy.artisan.queueFlush": QueueFlushCommand,
    "laravelLegacy.artisan.queueForget": QueueForgetCommand,
    "laravelLegacy.artisan.queueRetry": QueueRetryCommand,
    "laravelLegacy.artisan.routeList": RouteListCommand,
    "laravelLegacy.artisan.scheduleList": ScheduleListCommand,
    "laravelLegacy.artisan.scheduleTest": ScheduleTestCommand,
    "laravelLegacy.artisan.schemaDump": SchemaDumpCommand,
    "laravelLegacy.artisan.wayfinderGenerate": WayfinderGenerateCommand,
    "laravelLegacy.artisan.cacheClear": CacheClearCommand,
    "laravelLegacy.artisan.vendorPublish": VendorPublishCommand,
};

export const registerArtisanCommands = () => {
    return Object.entries(artisanCommands).map(([name, command]) => {
        return vscode.commands.registerCommand(name, (uri: vscode.Uri) => {
            runArtisanCommand(command, uri);
        });
    });
};
