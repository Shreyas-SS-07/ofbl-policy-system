/**
 * OFBL Policy Management System — Client-Side Live Demo Data Store
 * Powered by LocalStorage for GitHub Pages Deployment
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 * Lead Developer: Shreyas Sankalp Sahu (KIIT CSE)
 */

const OFBL_INITIAL_DATA = {
    currentUser: {
        id: 1,
        name: 'Shreyas Sankalp Sahu',
        email: 'admin@ofbl.gov.in',
        role: 'admin',
        department: 'Information Technology Centre (ITC)',
        status: 'approved'
    },
    sections: [
        { id: 1, code: 'ITC', name: 'Information Technology Centre', desc: 'Network security, infrastructure & cyber hygiene directives', icon: 'bi-cpu' },
        { id: 2, code: 'PROD', name: 'Ordnance Production Division', desc: 'Ammunition assembly, machinery SOPs & manufacturing', icon: 'bi-gear-wide-connected' },
        { id: 3, code: 'DGQA', name: 'Quality Assurance & Inspection', desc: 'Defective tolerance benchmarks & calibration norms', icon: 'bi-shield-check' },
        { id: 4, code: 'SAFE', name: 'Industrial Safety & Explosives', desc: 'Hazardous chemicals, explosive safety & fire evacuation', icon: 'bi-exclamation-triangle' },
        { id: 5, code: 'FIN', name: 'Finance & Defence Accounts', desc: 'Procurement protocols & budget compliance guidelines', icon: 'bi-cash-coin' },
        { id: 6, code: 'HRD', name: 'Human Resource Development', desc: 'Code of conduct, apprenticeship & workplace regulations', icon: 'bi-people' }
    ],
    policies: [
        {
            id: 1,
            number: 'POL-ITC-2026-001',
            title: 'Cybersecurity & Official IT Usage Policy',
            sectionId: 1,
            sectionName: 'Information Technology Centre',
            sectionCode: 'ITC',
            date: '2026-04-01',
            version: '2.1',
            pdfFile: 'uploads/pdfs/OFBL_IT_Security_Policy_2026.pdf',
            hint: 'Mandatory cyber hygiene, email confidentiality, and authorized device usage rules for OFBL intranet.',
            sizeKb: 245,
            downloads: 48,
            status: 'active'
        },
        {
            id: 2,
            number: 'POL-SAFE-2026-002',
            title: 'Ordnance Safety & Hazardous Materials Protocol',
            sectionId: 4,
            sectionName: 'Industrial Safety & Explosives',
            sectionCode: 'SAFE',
            date: '2026-04-05',
            version: '1.4',
            pdfFile: 'uploads/pdfs/OFBL_Industrial_Safety_Protocol.pdf',
            hint: 'Operating safety directives inside explosive storage, PPE compliance, and emergency evacuation.',
            sizeKb: 312,
            downloads: 92,
            status: 'active'
        },
        {
            id: 3,
            number: 'POL-DGQA-2026-003',
            title: 'Munitions Production Quality Assurance Manual',
            sectionId: 3,
            sectionName: 'Quality Assurance & Inspection',
            sectionCode: 'DGQA',
            date: '2026-04-12',
            version: '3.0',
            pdfFile: 'uploads/pdfs/OFBL_Quality_Control_Standards.pdf',
            hint: 'Quality benchmarks, inspection sampling techniques, and calibration frequency for ordnance lots.',
            sizeKb: 410,
            downloads: 34,
            status: 'active'
        },
        {
            id: 4,
            number: 'POL-HRD-2026-004',
            title: 'Employee Code of Conduct & Ethics Regulations',
            sectionId: 6,
            sectionName: 'Human Resource Development',
            sectionCode: 'HRD',
            date: '2026-04-18',
            version: '1.2',
            pdfFile: 'uploads/pdfs/OFBL_Employee_Conduct_Regulations.pdf',
            hint: 'Disciplinary rules, working hours, leave provisions, and confidentiality obligations for MIL personnel.',
            sizeKb: 198,
            downloads: 65,
            status: 'active'
        },
        {
            id: 5,
            number: 'POL-ADM-2026-005',
            title: 'Defence Information Handling & Document Classification SOP',
            sectionId: 1,
            sectionName: 'Information Technology Centre',
            sectionCode: 'ITC',
            date: '2026-04-25',
            version: '1.0',
            pdfFile: 'uploads/pdfs/OFBL_Information_Classification_SOP.pdf',
            hint: 'Guidelines on handling classified documents, physical and digital record archiving, and non-disclosure.',
            sizeKb: 280,
            downloads: 29,
            status: 'active'
        }
    ],
    users: [
        { id: 1, name: 'Shreyas Sankalp Sahu', email: 'admin@ofbl.gov.in', role: 'admin', dept: 'ITC', status: 'approved', joined: '04 May 2026' },
        { id: 2, name: 'Smt. Minati Pradhan', email: 'minati.pradhan@ofbl.gov.in', role: 'admin', dept: 'ITC', status: 'approved', joined: '04 May 2026' },
        { id: 3, name: 'Rajesh Kumar Sharma', email: 'rajesh.sharma@ofbl.gov.in', role: 'user', dept: 'PROD', status: 'approved', joined: '10 May 2026' },
        { id: 4, name: 'Pooja Verma', email: 'pooja.verma@ofbl.gov.in', role: 'user', dept: 'SAFE', status: 'approved', joined: '12 May 2026' },
        { id: 5, name: 'Alok Mohanty', email: 'alok.mohanty@ofbl.gov.in', role: 'user', dept: 'DGQA', status: 'pending', joined: '18 May 2026' }
    ],
    comments: [
        { id: 1, policyId: 1, author: 'Rajesh Kumar Sharma', text: 'Requesting clarification regarding multi-factor authentication requirements for remote intranet terminals.', date: '06 May 2026, 11:20 AM' },
        { id: 2, policyId: 2, author: 'Pooja Verma', text: 'Annual safety drill schedule for Ordnance Bay-4 has been coordinated in compliance with Section 4.2.', date: '08 May 2026, 02:45 PM' },
        { id: 3, policyId: 3, author: 'Rajesh Kumar Sharma', text: 'Received the updated inspection checklist. Calibration of batch gauges completed yesterday.', date: '14 May 2026, 04:10 PM' }
    ],
    auditLogs: [
        { id: 1, user: 'Shreyas Sankalp Sahu', action: 'SYSTEM_INITIALIZATION', details: 'OFBL Policy Governance Core v2.0 initialized', ip: '127.0.0.1', time: '04 May 2026, 09:00 AM' },
        { id: 2, user: 'Shreyas Sankalp Sahu', action: 'POLICY_UPLOAD', details: 'Published policy POL-ITC-2026-001 (Cybersecurity)', ip: '127.0.0.1', time: '04 May 2026, 10:05 AM' },
        { id: 3, user: 'Shreyas Sankalp Sahu', action: 'USER_APPROVED', details: 'Approved clearance for Rajesh Kumar Sharma', ip: '127.0.0.1', time: '10 May 2026, 10:18 AM' },
        { id: 4, user: 'Rajesh Kumar Sharma', action: 'POLICY_VIEW', details: 'Inspected PDF for POL-SAFE-2026-002', ip: '192.168.1.45', time: '12 May 2026, 11:40 AM' }
    ]
};

class OFBLDataStore {
    constructor() {
        const stored = localStorage.getItem('ofbl_portal_store');
        if (stored) {
            try {
                this.data = JSON.parse(stored);
            } catch (e) {
                this.data = JSON.parse(JSON.stringify(OFBL_INITIAL_DATA));
                this.save();
            }
        } else {
            this.data = JSON.parse(JSON.stringify(OFBL_INITIAL_DATA));
            this.save();
        }
    }
    save() {
        localStorage.setItem('ofbl_portal_store', JSON.stringify(this.data));
    }
    reset() {
        this.data = JSON.parse(JSON.stringify(OFBL_INITIAL_DATA));
        this.save();
    }
    getStats() {
        const pendingCount = this.data.users.filter(u => u.status === 'pending').length;
        return {
            policies: this.data.policies.length,
            users: this.data.users.length,
            sections: this.data.sections.length,
            comments: this.data.comments.length,
            pendingUsers: pendingCount
        };
    }
    getSectionDistribution() {
        const counts = {};
        this.data.sections.forEach(s => counts[s.name] = 0);
        this.data.policies.forEach(p => {
            if (counts[p.sectionName] !== undefined) {
                counts[p.sectionName]++;
            } else {
                counts[p.sectionName] = 1;
            }
        });
        return {
            labels: Object.keys(counts),
            data: Object.values(counts)
        };
    }
    addPolicy(policy) {
        policy.id = Date.now();
        policy.downloads = 0;
        policy.status = 'active';
        this.data.policies.unshift(policy);
        this.data.auditLogs.unshift({
            id: Date.now(),
            user: this.data.currentUser.name,
            action: 'POLICY_UPLOAD',
            details: `Published policy: ${policy.number} - ${policy.title}`,
            ip: '127.0.0.1',
            time: 'Just now'
        });
        this.save();
    }
    deletePolicy(id) {
        const p = this.data.policies.find(item => item.id === id);
        this.data.policies = this.data.policies.filter(item => item.id !== id);
        if (p) {
            this.data.auditLogs.unshift({
                id: Date.now(),
                user: this.data.currentUser.name,
                action: 'POLICY_DELETE',
                details: `Deleted policy: ${p.number}`,
                ip: '127.0.0.1',
                time: 'Just now'
            });
        }
        this.save();
    }
    approveUser(id) {
        const u = this.data.users.find(item => item.id === id);
        if (u) {
            u.status = 'approved';
            this.data.auditLogs.unshift({
                id: Date.now(),
                user: this.data.currentUser.name,
                action: 'USER_APPROVE',
                details: `Approved clearance for ${u.name}`,
                ip: '127.0.0.1',
                time: 'Just now'
            });
            this.save();
        }
    }
    addComment(policyId, text) {
        this.data.comments.unshift({
            id: Date.now(),
            policyId: policyId,
            author: this.data.currentUser.name,
            text: text,
            date: 'Just now'
        });
        this.save();
    }
}
window.ofblStore = new OFBLDataStore();
